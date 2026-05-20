<?php

namespace App\Services;

use App\Models\AkunGroup;
use App\Models\JurnalUmum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class NeracaService
{
    public function hitungMulti(array $periodeList, string $jenisFilter = 'bulan'): array
    {
        if (empty($periodeList)) {
            return [];
        }

        $groups = $this->loadGroups();
        $result = [];

        foreach ($periodeList as $periode) {
            $start = $periode['start']; // Instance Carbon
            $end   = $periode['end'];   // Instance Carbon
            $key   = $periode['date_string'];

            $saldo            = $this->getSaldoDinamis($start, $end, $jenisFilter);
            $qty              = $this->getSaldoQtyDinamis($start, $end, $jenisFilter);
            $labaRugiBerjalan = $this->hitungLabaRugiBerjalanDinamis($start, $end);

            $result[$key] = array_merge(
                [
                    'label' => $periode['label'], 
                    'tahun' => $periode['tahun'], 
                    'bulan' => $periode['bulan']
                ],
                $this->buildNeraca($groups, $saldo, $qty, $labaRugiBerjalan)
            );
        }

        return $result;
    }

    private function getSaldoDinamis(Carbon $start, Carbon $end, string $jenisFilter): array
    {
        $saldoAwal = [];
        $saldoNormalMap = DB::table('sub_anak_akuns')->pluck('saldo_normal', 'kode_sub_anak_akun')->toArray();

        if ($jenisFilter === 'hari') {
            // Akumulasi jurnal dari awal mula s.d H-1
            $mutasiLalu = JurnalUmum::where('tgl', '<', $start->format('Y-m-d'))
                ->selectRaw("
                    no_akun,
                    SUM(CASE WHEN LOWER(map) = 'd' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_debit,
                    SUM(CASE WHEN LOWER(map) = 'k' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_kredit
                ")
                ->groupBy('no_akun')
                ->get()
                ->keyBy('no_akun');

            foreach ($mutasiLalu as $kode => $m) {
                $isKredit = in_array(strtolower($saldoNormalMap[$kode] ?? 'debit'), ['kredit', 'credit', 'k']);
                $saldoAwal[$kode] = $isKredit 
                    ? ($m->total_kredit - $m->total_debit) 
                    : ($m->total_debit - $m->total_kredit);
            }
        } else {
            // Jika bulanan, pakai tabel buku_besar bulan lalu agar efisien
            $prevDate  = $start->copy()->subMonth();
            $saldoAwal = DB::table('buku_besar')
                ->where('tahun', $prevDate->year)
                ->where('bulan', $prevDate->month)
                ->pluck('saldo', 'no_akun')
                ->toArray();
        }

        // Mutasi pada rentang tanggal/bulan terpilih
        $mutasi = JurnalUmum::whereBetween('tgl', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->selectRaw("
                no_akun,
                SUM(CASE WHEN LOWER(map) = 'd' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_debit,
                SUM(CASE WHEN LOWER(map) = 'k' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_kredit
            ")
            ->groupBy('no_akun')
            ->get()
            ->keyBy('no_akun');

        $semuaKode = collect(array_keys($saldoAwal))->merge($mutasi->keys())->unique();

        $result = [];
        foreach ($semuaKode as $kode) {
            $awal   = (float) ($saldoAwal[$kode] ?? 0);
            $debit  = (float) ($mutasi[$kode]->total_debit  ?? 0);
            $kredit = (float) ($mutasi[$kode]->total_kredit ?? 0);

            $isKredit = in_array(strtolower($saldoNormalMap[$kode] ?? 'debit'), ['kredit', 'credit', 'k']);
            $result[$kode] = $isKredit ? $awal + $kredit - $debit : $awal + $debit - $kredit;
        }

        return $result;
    }

    private function getSaldoQtyDinamis(Carbon $start, Carbon $end, string $jenisFilter): array
    {
        $qtyAwal = [];
        $saldoNormalMap = DB::table('sub_anak_akuns')->pluck('saldo_normal', 'kode_sub_anak_akun')->toArray();

        if ($jenisFilter === 'hari') {
            $mutasiQtyLalu = JurnalUmum::where('tgl', '<', $start->format('Y-m-d'))
                ->whereNotNull('banyak')->where('banyak', '>', 0)
                ->selectRaw("
                    no_akun,
                    SUM(CASE WHEN LOWER(map) = 'd' THEN COALESCE(banyak, 0) ELSE 0 END) as qty_debit,
                    SUM(CASE WHEN LOWER(map) = 'k' THEN COALESCE(banyak, 0) ELSE 0 END) as qty_kredit
                ")
                ->groupBy('no_akun')
                ->get()
                ->keyBy('no_akun');

            foreach ($mutasiQtyLalu as $kode => $m) {
                $isKredit = in_array(strtolower($saldoNormalMap[$kode] ?? 'debit'), ['kredit', 'credit', 'k']);
                $qtyAwal[$kode] = $isKredit 
                    ? ($m->qty_kredit - $m->qty_debit) 
                    : ($m->qty_debit - $m->qty_kredit);
            }
        } else {
            $prevDate = $start->copy()->subMonth();
            try {
                $qtyAwal = DB::table('buku_besar')
                    ->where('tahun', $prevDate->year)
                    ->where('bulan', $prevDate->month)
                    ->whereNotNull('qty')->where('qty', '>', 0)
                    ->pluck('qty', 'no_akun')->toArray();
            } catch (\Exception $e) { $qtyAwal = []; }
        }

        $mutasiQty = JurnalUmum::whereBetween('tgl', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotNull('banyak')->where('banyak', '>', 0)
            ->selectRaw("
                no_akun,
                SUM(CASE WHEN LOWER(map) = 'd' THEN COALESCE(banyak, 0) ELSE 0 END) as qty_debit,
                SUM(CASE WHEN LOWER(map) = 'k' THEN COALESCE(banyak, 0) ELSE 0 END) as qty_kredit
            ")
            ->groupBy('no_akun')
            ->get()
            ->keyBy('no_akun');

        $semuaKode = collect(array_keys($qtyAwal))->merge($mutasiQty->keys())->unique();
        
        $result = [];
        foreach ($semuaKode as $kode) {
            $awal = (float) ($qtyAwal[$kode] ?? 0);
            $qtyD = (float) ($mutasiQty[$kode]->qty_debit  ?? 0);
            $qtyK = (float) ($mutasiQty[$kode]->qty_kredit ?? 0);

            if ($qtyD == 0 && $qtyK == 0 && $awal == 0) continue;

            $isKredit = in_array(strtolower($saldoNormalMap[$kode] ?? 'debit'), ['kredit', 'credit', 'k']);
            $net = $isKredit ? $awal + $qtyK - $qtyD : $awal + $qtyD - $qtyK;

            if ($net != 0) $result[$kode] = $net;
        }

        return $result;
    }

    private function hitungLabaRugiBerjalanDinamis(Carbon $start, Carbon $end): float
    {
        $rootLabaRugi = DB::table('akun_groups')->whereNull('parent_id')
            ->whereRaw('LOWER(nama) LIKE ?', ['%laba rugi%'])->first();

        if (!$rootLabaRugi) return 0.0;

        $allGroupIds = $this->getAllChildGroupIds($rootLabaRugi->id);
        if (empty($allGroupIds)) return 0.0;

        $akunLabaRugi = DB::table('akun_group_sub_anak_akun as pivot')
            ->join('sub_anak_akuns as saa', 'saa.id', '=', 'pivot.sub_anak_akun_id')
            ->whereIn('pivot.akun_group_id', $allGroupIds)
            ->select('saa.kode_sub_anak_akun', 'saa.saldo_normal')
            ->get()->keyBy('kode_sub_anak_akun');

        if ($akunLabaRugi->isEmpty()) return 0.0;

        $mutasi = JurnalUmum::whereBetween('tgl', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereIn('no_akun', $akunLabaRugi->keys()->toArray())
            ->selectRaw("
                no_akun,
                SUM(CASE WHEN LOWER(map) = 'd' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_debit,
                SUM(CASE WHEN LOWER(map) = 'k' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_kredit
            ")
            ->groupBy('no_akun')->get()->keyBy('no_akun');

        $laba = 0.0;
        foreach ($akunLabaRugi as $kode => $akun) {
            $debit  = (float) ($mutasi[$kode]->total_debit  ?? 0);
            $kredit = (float) ($mutasi[$kode]->total_kredit ?? 0);
            $isKredit = in_array(strtolower($akun->saldo_normal ?? 'debit'), ['kredit', 'credit', 'k']);
            
            if ($isKredit) {
                $laba += $kredit - $debit;
            } else {
                $laba -= $debit - $kredit;
            }
        }

        return $laba;
    }

    private function getAllChildGroupIds(int $parentId): array
    {
        $ids      = [];
        $children = DB::table('akun_groups')
            ->where('parent_id', $parentId)
            ->pluck('id')
            ->toArray();

        foreach ($children as $childId) {
            $ids[] = $childId;
            $ids   = array_merge($ids, $this->getAllChildGroupIds($childId));
        }
        return $ids;
    }

    private function loadGroups(): Collection
    {
        return AkunGroup::with([
            'subAnakAkuns' => fn($q) => $q->orderBy('kode_sub_anak_akun')
                ->select(['sub_anak_akuns.id','id_anak_akun','kode_sub_anak_akun','nama_sub_anak_akun','saldo_normal']),
            'childrenRecursive.subAnakAkuns' => fn($q) => $q->orderBy('kode_sub_anak_akun')
                ->select(['sub_anak_akuns.id','id_anak_akun','kode_sub_anak_akun','nama_sub_anak_akun','saldo_normal']),
            'childrenRecursive.anakAkuns' => fn($q) => $q->orderBy('kode_anak_akun')
                ->with([
                    'subAnakAkuns' => fn($q2) => $q2->orderBy('kode_sub_anak_akun')
                        ->select(['sub_anak_akuns.id','id_anak_akun','kode_sub_anak_akun','nama_sub_anak_akun','saldo_normal']),
                    'children' => fn($q3) => $q3->orderBy('kode_anak_akun')
                        ->with([
                            'subAnakAkuns' => fn($q4) => $q4->orderBy('kode_sub_anak_akun')
                                ->select(['sub_anak_akuns.id','id_anak_akun','kode_sub_anak_akun','nama_sub_anak_akun','saldo_normal']),
                        ]),
                ]),
        ])->whereNull('parent_id')->visible()->ordered()->get();
    }

    private function buildNeraca(Collection $groups, array $saldo, array $qty = [], float $labaRugiBerjalan = 0.0): array
    {
        $aktiva = ['sections' => [], 'total' => 0.0];
        $pasiva = ['sections' => [], 'total' => 0.0];

        foreach ($groups as $rootGroup) {
            $namaUpper = strtoupper(trim($rootGroup->nama));

            if ($rootGroup->childrenRecursive->isEmpty()) {
                [$sections, $totalRoot] = $this->buildSectionsFromRoot($rootGroup, $saldo, $qty);
            } else {
                [$sections, $totalRoot] = $this->buildSections($rootGroup->childrenRecursive, $saldo, $qty);
            }

            $data = ['sections' => $sections, 'total' => $totalRoot];

            if (str_contains($namaUpper, 'AKTIVA')) {
                $aktiva = $data;
            } elseif (str_contains($namaUpper, 'PASIVA')) {
                $pasiva = $data;
            }
        }

        if ($labaRugiBerjalan != 0) {
            $pasiva['sections'][] = [
                'group'        => 'Laba Rugi Berjalan',
                'items'        => [[
                    'kode'  => '—',
                    'nama'  => 'Laba (Rugi) Periode Berjalan',
                    'nilai' => $labaRugiBerjalan,
                    'qty'   => null,
                ]],
                'total'        => $labaRugiBerjalan,
                'sub_sections' => [],
            ];
            $pasiva['total'] += $labaRugiBerjalan;
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

        return [[[
            'group'        => $rootGroup->nama,
            'items'        => $items,
            'total'        => $total,
            'sub_sections' => [],
        ]], $total];
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
                [$subSections, $totalBranch] = $this->buildSections($group->children, $saldo, $qty);
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