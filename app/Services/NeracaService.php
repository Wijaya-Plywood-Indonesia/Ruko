<?php

namespace App\Services;

use App\Models\AkunGroup;
use App\Models\JurnalUmum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class NeracaService
{
    public function hitungMulti(array $periodeList): array
    {
        if (empty($periodeList)) {
            return [];
        }

        $groups = $this->loadGroups();
        $result = [];

        foreach ($periodeList as $periode) {
            $tahun = (int) $periode['tahun'];
            $bulan = (int) $periode['bulan'];

            $saldo = $this->getSaldo($tahun, $bulan);
            $qty   = $this->getSaldoQty($tahun, $bulan);

            $key   = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);
            $label = Carbon::create($tahun, $bulan)->locale('id')->isoFormat('MMMM Y');

            $result[$key] = array_merge(
                ['label' => $label, 'tahun' => $tahun, 'bulan' => $bulan],
                $this->buildNeraca($groups, $saldo, $qty)
            );
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────

    private function loadGroups(): Collection
    {
        return AkunGroup::with([
            'subAnakAkuns' => fn($q) => $q
                ->orderBy('kode_sub_anak_akun')
                ->select([
                    'sub_anak_akuns.id',
                    'id_anak_akun',
                    'kode_sub_anak_akun',
                    'nama_sub_anak_akun',
                    'saldo_normal',
                ]),

            'childrenRecursive.subAnakAkuns' => fn($q) => $q
                ->orderBy('kode_sub_anak_akun')
                ->select([
                    'sub_anak_akuns.id',
                    'id_anak_akun',
                    'kode_sub_anak_akun',
                    'nama_sub_anak_akun',
                    'saldo_normal',
                ]),

            'childrenRecursive.anakAkuns' => fn($q) => $q
                ->orderBy('kode_anak_akun')
                ->with([
                    'subAnakAkuns' => fn($q2) => $q2
                        ->orderBy('kode_sub_anak_akun')
                        ->select([
                            'sub_anak_akuns.id',
                            'id_anak_akun',
                            'kode_sub_anak_akun',
                            'nama_sub_anak_akun',
                            'saldo_normal',
                        ]),
                    'children' => fn($q3) => $q3
                        ->orderBy('kode_anak_akun')
                        ->with([
                            'subAnakAkuns' => fn($q4) => $q4
                                ->orderBy('kode_sub_anak_akun')
                                ->select([
                                    'sub_anak_akuns.id',
                                    'id_anak_akun',
                                    'kode_sub_anak_akun',
                                    'nama_sub_anak_akun',
                                    'saldo_normal',
                                ]),
                        ]),
                ]),
        ])
            ->whereNull('parent_id')
            ->visible()
            ->ordered()
            ->get();
    }

    /**
     * Hitung saldo akhir (nilai Rp) setiap akun per bulan.
     */
    private function getSaldo(int $tahun, int $bulan): array
    {
        $start = Carbon::create($tahun, $bulan)->startOfMonth();
        $end   = Carbon::create($tahun, $bulan)->endOfMonth();

        $prevDate  = Carbon::create($tahun, $bulan)->subMonth();
        $saldoAwal = DB::table('buku_besar')
            ->where('tahun', $prevDate->year)
            ->where('bulan', $prevDate->month)
            ->pluck('saldo', 'no_akun')
            ->toArray();

        $mutasi = JurnalUmum::whereBetween('tgl', [$start, $end])
            ->selectRaw("
                no_akun,
                SUM(CASE WHEN LOWER(map) = 'd' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_debit,
                SUM(CASE WHEN LOWER(map) = 'k' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_kredit
            ")
            ->groupBy('no_akun')
            ->get()
            ->keyBy('no_akun');

        $semuaKode = collect(array_keys($saldoAwal))
            ->merge($mutasi->keys())
            ->unique();

        $saldoNormalMap = DB::table('sub_anak_akuns')
            ->pluck('saldo_normal', 'kode_sub_anak_akun')
            ->toArray();

        $result = [];
        foreach ($semuaKode as $kode) {
            $awal   = (float) ($saldoAwal[$kode] ?? 0);
            $debit  = (float) ($mutasi[$kode]->total_debit  ?? 0);
            $kredit = (float) ($mutasi[$kode]->total_kredit ?? 0);

            $saldoNormal = strtolower($saldoNormalMap[$kode] ?? 'debit');
            $isKredit    = in_array($saldoNormal, ['kredit', 'credit', 'k']);

            $result[$kode] = $isKredit
                ? $awal + $kredit - $debit
                : $awal + $debit - $kredit;
        }

        return $result;
    }

    /**
     * Hitung saldo qty akhir setiap akun per bulan.
     * Hanya akun yang benar-benar punya data qty (banyak > 0) yang masuk.
     * Return: [ 'kode' => float ] — akun tanpa qty tidak ada di array ini.
     */
    private function getSaldoQty(int $tahun, int $bulan): array
    {
        $start = Carbon::create($tahun, $bulan)->startOfMonth();
        $end   = Carbon::create($tahun, $bulan)->endOfMonth();

        // Qty awal dari snapshot buku_besar bulan sebelumnya (kolom qty opsional)
        $prevDate = Carbon::create($tahun, $bulan)->subMonth();
        try {
            $qtyAwal = DB::table('buku_besar')
                ->where('tahun', $prevDate->year)
                ->where('bulan', $prevDate->month)
                ->whereNotNull('qty')
                ->where('qty', '>', 0)
                ->pluck('qty', 'no_akun')
                ->toArray();
        } catch (\Exception $e) {
            $qtyAwal = [];
        }

        // Mutasi qty bulan ini — hanya akun yang punya banyak > 0
        $mutasiQty = JurnalUmum::whereBetween('tgl', [$start, $end])
            ->whereNotNull('banyak')
            ->where('banyak', '>', 0)   // ← perbaikan: filter banyak > 0
            ->selectRaw("
                no_akun,
                SUM(CASE WHEN LOWER(map) = 'd' THEN COALESCE(banyak, 0) ELSE 0 END) as qty_debit,
                SUM(CASE WHEN LOWER(map) = 'k' THEN COALESCE(banyak, 0) ELSE 0 END) as qty_kredit
            ")
            ->groupBy('no_akun')
            ->get()
            ->keyBy('no_akun');

        if ($mutasiQty->isEmpty() && empty($qtyAwal)) {
            return [];
        }

        $semuaKode = collect(array_keys($qtyAwal))
            ->merge($mutasiQty->keys())
            ->unique();

        $saldoNormalMap = DB::table('sub_anak_akuns')
            ->pluck('saldo_normal', 'kode_sub_anak_akun')
            ->toArray();

        $result = [];
        foreach ($semuaKode as $kode) {
            $awal = (float) ($qtyAwal[$kode] ?? 0);
            $qtyD = (float) ($mutasiQty[$kode]->qty_debit  ?? 0);
            $qtyK = (float) ($mutasiQty[$kode]->qty_kredit ?? 0);

            // Skip jika tidak ada data qty sama sekali
            if ($qtyD == 0 && $qtyK == 0 && $awal == 0) {
                continue;
            }

            $saldoNormal = strtolower($saldoNormalMap[$kode] ?? 'debit');
            $isKredit    = in_array($saldoNormal, ['kredit', 'credit', 'k']);

            $net = $isKredit
                ? $awal + $qtyK - $qtyD
                : $awal + $qtyD - $qtyK;

            // Hanya masukkan jika hasil akhir tidak nol
            if ($net != 0) {
                $result[$kode] = $net;
            }
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────

    private function buildNeraca(Collection $groups, array $saldo, array $qty = []): array
    {
        $aktiva = ['sections' => [], 'total' => 0.0];
        $pasiva = ['sections' => [], 'total' => 0.0];

        foreach ($groups as $rootGroup) {
            $namaUpper = strtoupper(trim($rootGroup->nama));

            if ($rootGroup->childrenRecursive->isEmpty()) {
                [$sections, $totalRoot] = $this->buildSectionsFromRoot($rootGroup, $saldo, $qty);
            } else {
                [$sections, $totalRoot] = $this->buildSections(
                    $rootGroup->childrenRecursive,
                    $saldo,
                    $qty
                );
            }

            $data = ['sections' => $sections, 'total' => $totalRoot];

            if (str_contains($namaUpper, 'AKTIVA')) {
                $aktiva = $data;
            } elseif (str_contains($namaUpper, 'PASIVA')) {
                $pasiva = $data;
            }
        }

        return [
            'aktiva'      => $aktiva,
            'pasiva'      => $pasiva,
            'totalAktiva' => $aktiva['total'],
            'totalPasiva' => $pasiva['total'],
        ];
    }

    private function buildSectionsFromRoot(AkunGroup $rootGroup, array $saldo, array $qty = []): array
    {
        $items = [];
        $total = 0.0;

        $subs = $rootGroup->relationLoaded('subAnakAkuns')
            ? $rootGroup->subAnakAkuns
            : $rootGroup->subAnakAkuns()->orderBy('kode_sub_anak_akun')->get();

        foreach ($subs as $sub) {
            $nilai = $saldo[$sub->kode_sub_anak_akun] ?? 0.0;
            $q     = isset($qty[$sub->kode_sub_anak_akun]) ? (float) $qty[$sub->kode_sub_anak_akun] : null;

            $items[] = [
                'kode'  => $sub->kode_sub_anak_akun,
                'nama'  => $sub->nama_sub_anak_akun,
                'nilai' => $nilai,
                'qty'   => $q,
            ];
            $total += $nilai;
        }

        $sections = [[
            'group'        => $rootGroup->nama,
            'items'        => $items,
            'total'        => $total,
            'sub_sections' => [],
        ]];

        return [$sections, $total];
    }

    private function buildSections(Collection $groups, array $saldo, array $qty = []): array
    {
        $sections = [];
        $totalAll = 0.0;

        foreach ($groups as $group) {
            $isLeaf = $group->children->isEmpty();

            if ($isLeaf) {
                $items        = [];
                $totalSection = 0.0;

                // Sub-pola B1: subAnakAkuns via pivot
                if ($group->relationLoaded('subAnakAkuns') && $group->subAnakAkuns->isNotEmpty()) {
                    foreach ($group->subAnakAkuns as $sub) {
                        $nilai = $saldo[$sub->kode_sub_anak_akun] ?? 0.0;
                        $q     = isset($qty[$sub->kode_sub_anak_akun]) ? (float) $qty[$sub->kode_sub_anak_akun] : null;

                        $items[] = [
                            'kode'  => $sub->kode_sub_anak_akun,
                            'nama'  => $sub->nama_sub_anak_akun,
                            'nilai' => $nilai,
                            'qty'   => $q,
                        ];
                        $totalSection += $nilai;
                    }
                }

                // Sub-pola B2: anakAkuns
                foreach ($group->anakAkuns as $anakAkun) {
                    $nilaiAkun = $this->hitungNilaiAkun($anakAkun, $saldo);

                    $items[] = [
                        'kode'  => $anakAkun->kode_anak_akun,
                        'nama'  => $anakAkun->nama_anak_akun,
                        'nilai' => $nilaiAkun,
                        'qty'   => null,
                    ];
                    $totalSection += $nilaiAkun;
                }

                $sections[] = [
                    'group'        => $group->nama,
                    'items'        => $items,
                    'total'        => $totalSection,
                    'sub_sections' => [],
                ];

                $totalAll += $totalSection;

            } else {
                [$subSections, $totalBranch] = $this->buildSections(
                    $group->children,
                    $saldo,
                    $qty
                );

                $sections[] = [
                    'group'        => $group->nama,
                    'items'        => [],
                    'total'        => $totalBranch,
                    'sub_sections' => $subSections,
                ];

                $totalAll += $totalBranch;
            }
        }

        return [$sections, $totalAll];
    }

    private function hitungNilaiAkun($anakAkun, array $saldo): float
    {
        $total = 0.0;

        if ($anakAkun->subAnakAkuns->isNotEmpty()) {
            foreach ($anakAkun->subAnakAkuns as $sub) {
                $total += $saldo[$sub->kode_sub_anak_akun] ?? 0.0;
            }
        } elseif ($anakAkun->children->isNotEmpty()) {
            foreach ($anakAkun->children as $child) {
                $total += $this->hitungNilaiAkun($child, $saldo);
            }
        } else {
            $total = $saldo[$anakAkun->kode_anak_akun] ?? 0.0;
        }

        return $total;
    }

    
}