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

            $key   = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);
            $label = Carbon::create($tahun, $bulan)->locale('id')->isoFormat('MMMM Y');

            $result[$key] = array_merge(
                ['label' => $label, 'tahun' => $tahun, 'bulan' => $bulan],
                $this->buildNeraca($groups, $saldo)
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
     * Hitung saldo akhir setiap akun per bulan dari jurnal_umum.
     *
     * Logika sama dengan BukuBesar.php:
     *   saldo_awal  = snapshot buku_besar bulan sebelumnya (jika ada)
     *   mutasi      = SUM debit/kredit dari jurnal_umum bulan ini
     *   saldo_akhir = saldo_awal + debit - kredit  (untuk akun debit)
     *               = saldo_awal + kredit - debit  (untuk akun kredit)
     *
     * Return: [ 'kode_sub_anak_akun' => float ]
     */
    private function getSaldo(int $tahun, int $bulan): array
    {
        $start = Carbon::create($tahun, $bulan)->startOfMonth();
        $end   = Carbon::create($tahun, $bulan)->endOfMonth();

        // ── Saldo awal dari snapshot buku_besar bulan sebelumnya ──────────
        $prevDate    = Carbon::create($tahun, $bulan)->subMonth();
        $saldoAwal   = DB::table('buku_besar')
            ->where('tahun', $prevDate->year)
            ->where('bulan', $prevDate->month)
            ->pluck('saldo', 'no_akun')
            ->toArray();

        // ── Mutasi bulan ini dari jurnal_umum ────────────────────────────
        $mutasi = JurnalUmum::whereBetween('tgl', [$start, $end])
            ->selectRaw("
                no_akun,
                SUM(CASE WHEN LOWER(map) = 'd' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_debit,
                SUM(CASE WHEN LOWER(map) = 'k' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_kredit
            ")
            ->groupBy('no_akun')
            ->get()
            ->keyBy('no_akun');

        // ── Gabungkan semua kode akun yang ada ───────────────────────────
        $semuaKode = collect(array_keys($saldoAwal))
            ->merge($mutasi->keys())
            ->unique();

        // ── Hitung saldo akhir per akun ──────────────────────────────────
        // Kita butuh saldo_normal dari sub_anak_akuns untuk menentukan
        // apakah akun debit atau kredit
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

            if ($isKredit) {
                $result[$kode] = $awal + $kredit - $debit;
            } else {
                $result[$kode] = $awal + $debit - $kredit;
            }
        }

        return $result;
    }

    private function buildNeraca(Collection $groups, array $saldo): array
    {
        $aktiva = ['sections' => [], 'total' => 0.0];
        $pasiva = ['sections' => [], 'total' => 0.0];

        foreach ($groups as $rootGroup) {
            $namaUpper = strtoupper(trim($rootGroup->nama));

            if ($rootGroup->childrenRecursive->isEmpty()) {
                [$sections, $totalRoot] = $this->buildSectionsFromRoot($rootGroup, $saldo);
            } else {
                [$sections, $totalRoot] = $this->buildSections(
                    $rootGroup->childrenRecursive,
                    $saldo
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

    private function buildSectionsFromRoot(AkunGroup $rootGroup, array $saldo): array
    {
        $items = [];
        $total = 0.0;

        $subs = $rootGroup->relationLoaded('subAnakAkuns')
            ? $rootGroup->subAnakAkuns
            : $rootGroup->subAnakAkuns()->orderBy('kode_sub_anak_akun')->get();

        foreach ($subs as $sub) {
            $nilai = $saldo[$sub->kode_sub_anak_akun] ?? 0.0;

            $items[] = [
                'kode'  => $sub->kode_sub_anak_akun,
                'nama'  => $sub->nama_sub_anak_akun,
                'nilai' => $nilai,
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

    private function buildSections(Collection $groups, array $saldo): array
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

                        $items[] = [
                            'kode'  => $sub->kode_sub_anak_akun,
                            'nama'  => $sub->nama_sub_anak_akun,
                            'nilai' => $nilai,
                        ];
                        $totalSection += $nilai;
                    }
                }

                // Sub-pola B2: anakAkuns (relasi lama)
                foreach ($group->anakAkuns as $anakAkun) {
                    $nilaiAkun = $this->hitungNilaiAkun($anakAkun, $saldo);

                    $items[] = [
                        'kode'  => $anakAkun->kode_anak_akun,
                        'nama'  => $anakAkun->nama_anak_akun,
                        'nilai' => $nilaiAkun,
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
                    $saldo
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