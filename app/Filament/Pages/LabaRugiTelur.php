<?php

namespace App\Filament\Pages;

use App\Exports\LabaRugiExport;          // ← tambahan
use App\Models\AkunGroup;
use App\Models\SubAnakAkun;
use App\Models\JurnalUmum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;     // ← tambahan
use UnitEnum;

class LabaRugiTelur extends Page
{
    use HasPageShield;

    protected static string|UnitEnum|null $navigationGroup = 'Akuntansi Telur';
    protected static ?string $title = 'Laba Rugi Telur';
    protected static ?string $navigationLabel = 'Laba Rugi Telur';
    protected string $view = 'filament.pages.laba-rugi-telur';

    public string $periodeAwal;
    public string $periodeAkhir;
    public bool $tampilkanSaldoNol = false;

    public array $laporanData       = [];
    public array $bulanList         = [];
    public array $ringkasanPerBulan = [];
    public bool  $sudahFilter       = false;

    public function mount(): void
    {
        $now = now();
        $this->periodeAwal  = $now->format('Y-m');
        $this->periodeAkhir = $now->format('Y-m');
        $this->generateLaporan();
    }

    public function updatedPeriodeAwal(): void  { $this->generateLaporan(); }
    public function updatedPeriodeAkhir(): void { $this->generateLaporan(); }
    public function updatedTampilkanSaldoNol(): void {}

    public function buildPeriodeList(): array
    {
        try {
            $awal  = Carbon::createFromFormat('Y-m', $this->periodeAwal)->startOfMonth();
            $akhir = Carbon::createFromFormat('Y-m', $this->periodeAkhir)->startOfMonth();
        } catch (\Exception $e) {
            return [];
        }

        if ($awal->gt($akhir)) return [];

        if ($awal->diffInMonths($akhir) > 11) {
            $akhir = $awal->copy()->addMonths(11);
        }

        $list    = [];
        $current = $awal->copy();

        while ($current->lte($akhir)) {
            $list[] = [
                'tahun' => (int) $current->format('Y'),
                'bulan' => (int) $current->format('n'),
            ];
            $current->addMonth();
        }

        return $list;
    }

    public function jumlahPeriode(): int
    {
        return count($this->buildPeriodeList());
    }

    public function periodeValid(): bool
    {
        try {
            $awal  = Carbon::createFromFormat('Y-m', $this->periodeAwal);
            $akhir = Carbon::createFromFormat('Y-m', $this->periodeAkhir);
            return $awal->lte($akhir);
        } catch (\Exception $e) {
            return false;
        }
    }

    // ─── METHOD EXPORT EXCEL ──────────────────────────────────────────
    public function exportExcel(): mixed
    {
        if (empty($this->laporanData) || empty($this->bulanList)) {
            return null;
        }

        $periodeList = $this->bulanList;

        if (count($periodeList) === 1) {
            $p        = $periodeList[0];
            $filename = 'LabaRugi_' . $p['tahun'] . '-' . str_pad($p['bulan'], 2, '0', STR_PAD_LEFT) . '.xlsx';
        } else {
            $first    = $periodeList[0];
            $last     = $periodeList[count($periodeList) - 1];
            $filename = 'LabaRugi_'
                . $first['tahun'] . '-' . str_pad($first['bulan'], 2, '0', STR_PAD_LEFT)
                . '_sd_'
                . $last['tahun'] . '-' . str_pad($last['bulan'], 2, '0', STR_PAD_LEFT)
                . '.xlsx';
        }

        return Excel::download(
            new LabaRugiExport(
                laporanData:       $this->laporanData,
                bulanList:         $this->bulanList,
                ringkasanPerBulan: $this->ringkasanPerBulan,
                tampilkanSaldoNol: $this->tampilkanSaldoNol,
            ),
            $filename
        );
    }

    // ─── GENERATE LAPORAN ────────────────────────────────────────────
    public function generateLaporan(): void
    {
        $periodeList = $this->buildPeriodeList();

        if (empty($periodeList)) {
            $this->laporanData       = [];
            $this->bulanList         = [];
            $this->ringkasanPerBulan = [];
            $this->sudahFilter       = true;
            return;
        }

        $this->bulanList = $periodeList;

        $saldoPerPeriode = $this->getSaldoMapPerPeriode($periodeList);
        $qtyPerPeriode   = $this->getSaldoQtyPerPeriode($periodeList);

        $root = AkunGroup::whereNull('parent_id')
            ->whereRaw('LOWER(nama) LIKE ?', ['%laba rugi%'])
            ->first();

        if (!$root) {
            $this->laporanData       = [];
            $this->ringkasanPerBulan = [];
            $this->sudahFilter       = true;
            return;
        }

        $groups = AkunGroup::where('parent_id', $root->id)
            ->visible()
            ->ordered()
            ->with([
                'subAnakAkuns' => fn($q) => $q
                    ->orderBy('kode_sub_anak_akun')
                    ->select([
                        'sub_anak_akuns.id',
                        'id_anak_akun',
                        'kode_sub_anak_akun',
                        'nama_sub_anak_akun',
                        'saldo_normal',
                    ])
                    ->with(['anakAkun:id,kode_anak_akun,nama_anak_akun']),

                'childrenRecursive.subAnakAkuns' => fn($q) => $q
                    ->orderBy('kode_sub_anak_akun')
                    ->select([
                        'sub_anak_akuns.id',
                        'id_anak_akun',
                        'kode_sub_anak_akun',
                        'nama_sub_anak_akun',
                        'saldo_normal',
                    ])
                    ->with(['anakAkun:id,kode_anak_akun,nama_anak_akun']),

                'childrenRecursive.anakAkuns.subAnakAkuns',
                'anakAkuns.subAnakAkuns',
            ])
            ->get();

        $sections = [];
        foreach ($groups as $group) {
            $sections[] = $this->buildGroupNode($group, $saldoPerPeriode, $periodeList, $qtyPerPeriode);
        }

        $ringkasan = [];
        foreach ($periodeList as $periode) {
            $key = $this->periodeKey($periode);
            $r   = [
                'pendapatan'      => 0,
                'retur_potongan'  => 0,
                'hpp'             => 0,
                'beban_produksi'  => 0,
                'beban_usaha'     => 0,
                'pendapatan_lain' => 0,
                'beban_lain'      => 0,
            ];

            foreach ($sections as $section) {
                $tipe = $section['tipe'] ?? 'lainnya';
                if (isset($r[$tipe])) {
                    $r[$tipe] += $section['nilai_per_periode'][$key] ?? 0;
                }
            }

            $penjualanBersih = $r['pendapatan'] - $r['retur_potongan'];
            $totalHPP        = $r['hpp'] + $r['beban_produksi'];
            $labaKotor       = $penjualanBersih - $totalHPP;
            $labaUsaha       = $labaKotor - $r['beban_usaha'];
            $labaSblPajak    = $labaUsaha + $r['pendapatan_lain'] - $r['beban_lain'];

            $ringkasan[$key] = [
                'total_pendapatan'   => $r['pendapatan'],
                'penjualan_bersih'   => $penjualanBersih,
                'total_hpp'          => $totalHPP,
                'laba_kotor'         => $labaKotor,
                'laba_usaha'         => $labaUsaha,
                'laba_sebelum_pajak' => $labaSblPajak,
                'total_beban_usaha'  => $r['beban_usaha'],
            ];
        }

        $this->laporanData       = $sections;
        $this->ringkasanPerBulan = $ringkasan;
        $this->sudahFilter       = true;
    }

    public function periodeKey(array $periode): string
    {
        return $periode['tahun'] . '-' . str_pad($periode['bulan'], 2, '0', STR_PAD_LEFT);
    }

    public function labelPeriode(array $periode): string
    {
        return $this->getNamaBulan($periode['bulan']) . ' ' . $periode['tahun'];
    }

    private function getSaldoMapPerPeriode(array $periodeList): array
    {
        $saldoPerPeriode = [];
        $saldoNormalMap  = SubAnakAkun::pluck('saldo_normal', 'kode_sub_anak_akun')->toArray();

        foreach ($periodeList as $periode) {
            $key   = $this->periodeKey($periode);
            $start = Carbon::create($periode['tahun'], $periode['bulan'], 1)->startOfMonth();
            $end   = Carbon::create($periode['tahun'], $periode['bulan'], 1)->endOfMonth();

            $map     = [];
            $jurnals = JurnalUmum::whereBetween('tgl', [$start, $end])->get();

            foreach ($jurnals as $jurnal) {
                $kode  = $jurnal->no_akun;
                $nilai = (float) ($jurnal->banyak ?? 1) * (float) ($jurnal->harga ?? 0);

                $saldoNormal = strtolower($saldoNormalMap[$kode] ?? 'debit');
                $isDebit     = strtolower($jurnal->map) === 'd';
                $isKredit    = in_array($saldoNormal, ['kredit', 'credit', 'k']);

                if ($isKredit) {
                    $map[$kode] = ($map[$kode] ?? 0) + ($isDebit ? -$nilai : $nilai);
                } else {
                    $map[$kode] = ($map[$kode] ?? 0) + ($isDebit ? $nilai : -$nilai);
                }
            }

            $saldoPerPeriode[$key] = $map;
        }

        return $saldoPerPeriode;
    }

    private function buildGroupNode(AkunGroup $group, array $saldoPerPeriode, array $periodeList, array $qtyPerPeriode = []): array
    {
        $children        = [];
        $nilaiPerPeriode = array_fill_keys(array_map([$this, 'periodeKey'], $periodeList), 0.0);

        if ($group->subAnakAkuns->isNotEmpty()) {
            $perAnakAkun = $group->subAnakAkuns->groupBy('id_anak_akun');

            foreach ($perAnakAkun as $idAnakAkun => $subs) {
                $anakAkun = $subs->first()->anakAkun;
                if (!$anakAkun) continue;

                $subChildren   = [];
                $nilaiAnakAkun = array_fill_keys(array_map([$this, 'periodeKey'], $periodeList), 0.0);

                foreach ($subs->sortBy('kode_sub_anak_akun') as $sub) {
                    $subNode = $this->buildSubNode($sub, $saldoPerPeriode, $periodeList, $qtyPerPeriode);
                    $subChildren[] = $subNode;
                    foreach ($periodeList as $p) {
                        $k = $this->periodeKey($p);
                        $nilaiAnakAkun[$k] += $subNode['nilai_per_periode'][$k] ?? 0;
                    }
                }

                $anakNode = [
                    'type'              => 'anak_akun',
                    'kode'              => $anakAkun->kode_anak_akun,
                    'nama'              => $anakAkun->nama_anak_akun,
                    'children'          => $subChildren,
                    'nilai_per_periode' => $nilaiAnakAkun,
                    'nilai_per_bulan'   => $nilaiAnakAkun,
                ];

                $children[] = $anakNode;
                foreach ($periodeList as $p) {
                    $k = $this->periodeKey($p);
                    $nilaiPerPeriode[$k] += $nilaiAnakAkun[$k];
                }
            }
        }

        foreach ($group->children as $child) {
            $node = $this->buildGroupNode($child, $saldoPerPeriode, $periodeList, $qtyPerPeriode);
            $children[] = $node;
            foreach ($periodeList as $p) {
                $k = $this->periodeKey($p);
                $nilaiPerPeriode[$k] += $node['nilai_per_periode'][$k] ?? 0;
            }
        }

        if ($group->relationLoaded('anakAkuns')) {
            foreach ($group->anakAkuns as $anak) {
                $node = $this->buildAnakAkunNode($anak, $saldoPerPeriode, $periodeList, $qtyPerPeriode);
                $children[] = $node;
                foreach ($periodeList as $p) {
                    $k = $this->periodeKey($p);
                    $nilaiPerPeriode[$k] += $node['nilai_per_periode'][$k] ?? 0;
                }
            }
        }

        return [
            'type'              => 'group',
            'nama'              => $group->nama,
            'tipe'              => $group->tipe ?? 'lainnya',
            'hidden'            => (bool) $group->hidden,
            'children'          => $children,
            'nilai_per_periode' => $nilaiPerPeriode,
            'nilai_per_bulan'   => $nilaiPerPeriode,
        ];
    }

    private function buildSubNode(SubAnakAkun $sub, array $saldoPerPeriode, array $periodeList, array $qtyPerPeriode = []): array
    {
        $nilaiPerPeriode   = [];
        $qtyPerPeriodeNode = [];

        foreach ($periodeList as $p) {
            $key = $this->periodeKey($p);
            $nilaiPerPeriode[$key]    = (float) ($saldoPerPeriode[$key][$sub->kode_sub_anak_akun] ?? 0);
            $qtyPerPeriodeNode[$key]  = isset($qtyPerPeriode[$key][$sub->kode_sub_anak_akun])
                ? (float) $qtyPerPeriode[$key][$sub->kode_sub_anak_akun]
                : null;
        }

        return [
            'type'              => 'sub_anak_akun',
            'kode'              => $sub->kode_sub_anak_akun,
            'nama'              => $sub->nama_sub_anak_akun,
            'children'          => [],
            'nilai_per_periode' => $nilaiPerPeriode,
            'nilai_per_bulan'   => $nilaiPerPeriode,
            'qty_per_periode'   => $qtyPerPeriodeNode,
        ];
    }

    private function buildAnakAkunNode($anak, array $saldoPerPeriode, array $periodeList, array $qtyPerPeriode = []): array
    {
        $children        = [];
        $nilaiPerPeriode = array_fill_keys(array_map([$this, 'periodeKey'], $periodeList), 0.0);

        foreach ($anak->subAnakAkuns as $sub) {
            $node = $this->buildSubNode($sub, $saldoPerPeriode, $periodeList, $qtyPerPeriode);
            $children[] = $node;
            foreach ($periodeList as $p) {
                $nilaiPerPeriode[$this->periodeKey($p)] += $node['nilai_per_periode'][$this->periodeKey($p)] ?? 0;
            }
        }

        foreach ($periodeList as $p) {
            $nilaiPerPeriode[$this->periodeKey($p)] += (float) ($saldoPerPeriode[$this->periodeKey($p)][$anak->kode_anak_akun] ?? 0);
        }

        return [
            'type'              => 'anak_akun',
            'kode'              => $anak->kode_anak_akun,
            'nama'              => $anak->nama_anak_akun,
            'children'          => $children,
            'nilai_per_periode' => $nilaiPerPeriode,
            'nilai_per_bulan'   => $nilaiPerPeriode,
            'qty_per_periode'   => [],
        ];
    }

    public function getNamaBulan(int $bulan): string
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April',   5 => 'Mei',       6 => 'Juni',
            7 => 'Juli',    8 => 'Agustus',   9 => 'September',
            10 => 'Oktober',11 => 'November', 12 => 'Desember',
        ][$bulan] ?? '';
    }

    private function getSaldoQtyPerPeriode(array $periodeList): array
    {
        $qtyPerPeriode = [];

        foreach ($periodeList as $periode) {
            $key   = $this->periodeKey($periode);
            $start = Carbon::create($periode['tahun'], $periode['bulan'], 1)->startOfMonth();
            $end   = Carbon::create($periode['tahun'], $periode['bulan'], 1)->endOfMonth();

            $saldoNormalMap = SubAnakAkun::pluck('saldo_normal', 'kode_sub_anak_akun')->toArray();

            $mutasiQty = JurnalUmum::whereBetween('tgl', [$start, $end])
                ->whereNotNull('banyak')
                ->where('banyak', '>', 0)
                ->selectRaw("
                    no_akun,
                    SUM(CASE WHEN LOWER(map) = 'd' THEN COALESCE(banyak, 0) ELSE 0 END) as qty_debit,
                    SUM(CASE WHEN LOWER(map) = 'k' THEN COALESCE(banyak, 0) ELSE 0 END) as qty_kredit
                ")
                ->groupBy('no_akun')
                ->get()
                ->keyBy('no_akun');

            $map = [];
            foreach ($mutasiQty as $kode => $row) {
                $qtyD = (float) $row->qty_debit;
                $qtyK = (float) $row->qty_kredit;

                if ($qtyD == 0 && $qtyK == 0) continue;

                $saldoNormal = strtolower($saldoNormalMap[$kode] ?? 'debit');
                $isKredit    = in_array($saldoNormal, ['kredit', 'credit', 'k']);

                $net = $isKredit ? $qtyK - $qtyD : $qtyD - $qtyK;
                $map[$kode] = $net;
            }

            $qtyPerPeriode[$key] = $map;
        }

        return $qtyPerPeriode;
    }

    public function formatRupiah(float $nilai): string
    {
        return 'Rp ' . number_format(abs($nilai), 0, ',', '.');
    }
}