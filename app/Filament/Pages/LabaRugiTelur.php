<?php

namespace App\Filament\Pages;

use App\Models\AkunGroup;
use App\Models\SubAnakAkun;
use App\Models\JurnalUmum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Carbon\Carbon;
use UnitEnum;
use Illuminate\Support\Facades\DB;

class LabaRugiTelur extends Page
{
    use HasPageShield;

    protected static string|UnitEnum|null $navigationGroup = 'Akuntansi Telur';
    protected static ?string $title = 'Laba Rugi Telur';
    protected static ?string $navigationLabel = 'Laba Rugi Telur';
    protected string $view = 'filament.pages.laba-rugi-telur';

    public int $tahun;
    public int $bulan_dari;
    public int $bulan_sampai;

    public array $laporanData      = [];
    public array $bulanList        = [];
    public array $ringkasanPerBulan = [];
    public bool  $sudahFilter      = false;

    public function mount(): void
    {
        $this->tahun        = now()->year;
        $this->bulan_dari   = now()->month;
        $this->bulan_sampai = now()->month;

        $this->generateLaporan();
    }

    // ── Dipanggil saat dropdown berubah (wire:model.live) ────────────
    public function updatedTahun(): void        { $this->generateLaporan(); }
    public function updatedBulanDari(): void    { $this->generateLaporan(); }
    public function updatedBulanSampai(): void  { $this->generateLaporan(); }

    // ─────────────────────────────────────────────────────────────────
    // GENERATE
    // ─────────────────────────────────────────────────────────────────

    public function generateLaporan(): void
    {
        // Guard
        if ($this->bulan_dari > $this->bulan_sampai) {
            $this->bulan_sampai = $this->bulan_dari;
        }

        $bulanList = range($this->bulan_dari, $this->bulan_sampai);
        $this->bulanList = $bulanList;

        // Saldo per bulan dari jurnal
        $saldoPerBulan = $this->getSaldoMapPerBulan($bulanList);

        // Root group "Laba Rugi" (case-insensitive)
        $root = AkunGroup::whereNull('parent_id')
            ->whereRaw('LOWER(nama) LIKE ?', ['%laba rugi%'])
            ->first();

        if (!$root) {
            $this->laporanData       = [];
            $this->ringkasanPerBulan = [];
            $this->sudahFilter       = true;
            return;
        }

        // Children langsung di bawah root Laba Rugi
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
                'childrenRecursive.anakAkuns.subAnakAkuns',
                'anakAkuns.subAnakAkuns',
            ])
            ->get();

        // Build tree
        $sections = [];
        foreach ($groups as $group) {
            $sections[] = $this->buildGroupNode($group, $saldoPerBulan, $bulanList);
        }

        // Ringkasan per bulan
        $ringkasan = [];
        foreach ($bulanList as $bulan) {
            $r = [
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
                    $r[$tipe] += $section['nilai_per_bulan'][$bulan] ?? 0;
                }
            }

            $penjualanBersih = $r['pendapatan'] - $r['retur_potongan'];
            $totalHPP        = $r['hpp'] + $r['beban_produksi'];
            $labaKotor       = $penjualanBersih - $totalHPP;
            $labaUsaha       = $labaKotor - $r['beban_usaha'];
            $labaSblPajak    = $labaUsaha + $r['pendapatan_lain'] - $r['beban_lain'];

            $ringkasan[$bulan] = [
                'total_pendapatan'  => $r['pendapatan'],
                'penjualan_bersih'  => $penjualanBersih,
                'total_hpp'         => $totalHPP,
                'laba_kotor'        => $labaKotor,
                'laba_usaha'        => $labaUsaha,
                'laba_sebelum_pajak'=> $labaSblPajak,
            ];
        }

        $this->laporanData       = $sections;
        $this->ringkasanPerBulan = $ringkasan;
        $this->sudahFilter       = true;
    }

    // ─────────────────────────────────────────────────────────────────
    // SALDO MAP — sama dengan NeracaService & LabaRugi lama
    // ─────────────────────────────────────────────────────────────────

    private function getSaldoMapPerBulan(array $bulanList): array
    {
        $saldoPerBulan = [];

        // Ambil saldo_normal semua sub akun sekaligus
        $saldoNormalMap = SubAnakAkun::pluck('saldo_normal', 'kode_sub_anak_akun')->toArray();

        foreach ($bulanList as $bulan) {
            $start = Carbon::create($this->tahun, $bulan, 1)->startOfMonth();
            $end   = Carbon::create($this->tahun, $bulan, 1)->endOfMonth();

            $map = [];

            $jurnals = JurnalUmum::whereBetween('tgl', [$start, $end])->get();

            foreach ($jurnals as $jurnal) {
                $kode  = $jurnal->no_akun;
                $nilai = (float) ($jurnal->banyak ?? 1) * (float) ($jurnal->harga ?? 0);

                $saldoNormal = strtolower($saldoNormalMap[$kode] ?? 'debit');
                $isDebit     = strtolower($jurnal->map) === 'd';
                $isKredit    = in_array($saldoNormal, ['kredit', 'credit', 'k']);

                if ($isKredit) {
                    // Akun kredit: naik jika kredit, turun jika debit
                    $map[$kode] = ($map[$kode] ?? 0) + ($isDebit ? -$nilai : $nilai);
                } else {
                    // Akun debit: naik jika debit, turun jika kredit
                    $map[$kode] = ($map[$kode] ?? 0) + ($isDebit ? $nilai : -$nilai);
                }
            }

            $saldoPerBulan[$bulan] = $map;
        }

        return $saldoPerBulan;
    }

    // ─────────────────────────────────────────────────────────────────
    // BUILD TREE NODES
    // ─────────────────────────────────────────────────────────────────

    private function buildGroupNode(AkunGroup $group, array $saldoPerBulan, array $bulanList): array
    {
        $children      = [];
        $nilaiPerBulan = array_fill_keys($bulanList, 0.0);

        // Pola A: group langsung punya subAnakAkuns via pivot
        if ($group->subAnakAkuns->isNotEmpty()) {
            foreach ($group->subAnakAkuns as $sub) {
                $node = $this->buildSubNode($sub, $saldoPerBulan, $bulanList);
                $children[] = $node;
                foreach ($bulanList as $b) {
                    $nilaiPerBulan[$b] += $node['nilai_per_bulan'][$b] ?? 0;
                }
            }
        }

        // Pola B: group punya children groups (rekursif)
        foreach ($group->children as $child) {
            $node = $this->buildGroupNode($child, $saldoPerBulan, $bulanList);
            $children[] = $node;
            foreach ($bulanList as $b) {
                $nilaiPerBulan[$b] += $node['nilai_per_bulan'][$b] ?? 0;
            }
        }

        // Pola C: group punya anakAkuns (kompatibilitas relasi lama)
        if ($group->relationLoaded('anakAkuns')) {
            foreach ($group->anakAkuns as $anak) {
                $node = $this->buildAnakAkunNode($anak, $saldoPerBulan, $bulanList);
                $children[] = $node;
                foreach ($bulanList as $b) {
                    $nilaiPerBulan[$b] += $node['nilai_per_bulan'][$b] ?? 0;
                }
            }
        }

        return [
            'type'           => 'group',
            'nama'           => $group->nama,
            'tipe'           => $group->tipe ?? 'lainnya',
            'hidden'         => (bool) $group->hidden,
            'children'       => $children,
            'nilai_per_bulan'=> $nilaiPerBulan,
        ];
    }

    private function buildSubNode(SubAnakAkun $sub, array $saldoPerBulan, array $bulanList): array
    {
        $nilaiPerBulan = [];
        foreach ($bulanList as $b) {
            $nilaiPerBulan[$b] = (float) ($saldoPerBulan[$b][$sub->kode_sub_anak_akun] ?? 0);
        }

        return [
            'type'           => 'sub_anak_akun',
            'kode'           => $sub->kode_sub_anak_akun,
            'nama'           => $sub->nama_sub_anak_akun,
            'children'       => [],
            'nilai_per_bulan'=> $nilaiPerBulan,
        ];
    }

    private function buildAnakAkunNode($anak, array $saldoPerBulan, array $bulanList): array
    {
        $children      = [];
        $nilaiPerBulan = array_fill_keys($bulanList, 0.0);

        foreach ($anak->subAnakAkuns as $sub) {
            $node = $this->buildSubNode($sub, $saldoPerBulan, $bulanList);
            $children[] = $node;
            foreach ($bulanList as $b) {
                $nilaiPerBulan[$b] += $node['nilai_per_bulan'][$b] ?? 0;
            }
        }

        // Saldo langsung di level anak akun (tanpa sub)
        foreach ($bulanList as $b) {
            $nilaiPerBulan[$b] += (float) ($saldoPerBulan[$b][$anak->kode_anak_akun] ?? 0);
        }

        return [
            'type'           => 'anak_akun',
            'kode'           => $anak->kode_anak_akun,
            'nama'           => $anak->nama_anak_akun,
            'children'       => $children,
            'nilai_per_bulan'=> $nilaiPerBulan,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────

    public function getNamaBulan(int $bulan): string
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April',   5 => 'Mei',       6 => 'Juni',
            7 => 'Juli',    8 => 'Agustus',   9 => 'September',
            10 => 'Oktober',11 => 'November', 12 => 'Desember',
        ][$bulan] ?? '';
    }

    public function formatRupiah(float $nilai): string
    {
        return 'Rp ' . number_format(abs($nilai), 0, ',', '.');
    }
}   