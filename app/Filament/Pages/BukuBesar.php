<?php

namespace App\Filament\Pages;

use App\Models\IndukAkun;
use App\Models\JurnalUmum;
use App\Models\BukuBesar as BukuBesarModel;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Carbon\Carbon;
use UnitEnum;
use Illuminate\Support\Facades\DB;

class BukuBesar extends Page
{
    use HasPageShield;

    protected static string|UnitEnum|null $navigationGroup = 'Akuntansi Telur';
    protected string $view = 'filament.pages.buku-besar';
    protected static ?string $navigationLabel = 'Buku Besar';
    protected static ?string $title = 'Buku Besar';

    public $indukAkuns = [];
    public $filterBulan;
    public bool $isLoading = true;
    public $saldoMap = [];
    public $saldoAwalMap = [];

    public function mount(): void
    {
        $this->filterBulan = Carbon::now()->format('Y-m');
        // isLoading = true by default, initLoad akan dipanggil via wire:init
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return \Maatwebsite\Excel\Facades\Excel::download(
                        new \App\Exports\BukuBesarExport($this->filterBulan),
                        'Buku_Besar_' . $this->filterBulan . '.xlsx'
                    );
                }),
        ];
    }

    public function initLoad(): void
    {
        $this->preloadSaldoAwal();
        $this->preloadSaldo();
        $this->loadData();
        $this->isLoading = false;
    }

    public function updatedFilterBulan(): void
    {
        $this->isLoading    = true;
        $this->saldoAwalMap = [];
        $this->saldoMap     = [];

        $this->preloadSaldoAwal();
        $this->preloadSaldo();
        $this->loadData();
        $this->isLoading = false;
    }

    // ── Saldo akhir bulan SEBELUM periode terpilih (dari tabel buku_besar) ──
    private function preloadSaldoAwal(): void
    {
        $date = Carbon::parse($this->filterBulan)->subMonth();

        $this->saldoAwalMap = DB::table('buku_besar')
            ->where('tahun', $date->year)
            ->where('bulan', $date->month)
            ->pluck('saldo', 'no_akun')
            ->toArray();
    }

    // ── Mutasi bulan terpilih dari jurnal_umums ──────────────────────────────
    // Simpan debit dan kredit GROSS terpisah — bukan net
    private function preloadSaldo(): void
    {
        $start = Carbon::parse($this->filterBulan)->startOfMonth();
        $end   = Carbon::parse($this->filterBulan)->endOfMonth();

        $rows = JurnalUmum::whereBetween('tgl', [$start, $end])
            ->selectRaw("
                no_akun,
                SUM(CASE WHEN LOWER(map) = 'd' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_debit,
                SUM(CASE WHEN LOWER(map) = 'k' THEN COALESCE(banyak * harga, harga, 0) ELSE 0 END) as total_kredit
            ")
            ->groupBy('no_akun')
            ->get();

        $this->saldoMap = [];
        foreach ($rows as $row) {
            $this->saldoMap[$row->no_akun] = [
                'd' => (float) $row->total_debit,
                'k' => (float) $row->total_kredit,
            ];
        }
    }

    public function loadData(): void
    {
        $this->indukAkuns = IndukAkun::with([
            'anakAkuns' => function ($query) {
                $query->whereNull('parent')
                    ->with([
                        'subAnakAkuns',
                        'children' => function ($q) {
                            $q->with([
                                'subAnakAkuns',
                                'children' => function ($q2) {
                                    $q2->with(['subAnakAkuns']);
                                },
                            ]);
                        },
                    ]);
            },
        ])->get();
    }

    // ── Hitung nominal satu transaksi: cukup banyak × harga ─────────────────
    public function hitungNominal($trx): float
    {
        return (float) ($trx->banyak ?? 1) * (float) ($trx->harga ?? 0);
    }

    // ── Saldo awal (dari snapshot buku_besar bulan sebelumnya) ───────────────
    public function getSaldoAwal(string $kode): float
    {
        return (float) ($this->saldoAwalMap[$kode] ?? 0);
    }

    // ── Mutasi bulan ini untuk satu akun (debit gross) ───────────────────────
    public function getSaldoBulan(string $kode): float
    {
        return (float) ($this->saldoMap[$kode]['d'] ?? 0)
             - (float) ($this->saldoMap[$kode]['k'] ?? 0);
    }

    // ── Transaksi bulan terpilih untuk satu kode akun ───────────────────────
    public function getTransaksiByKode(string $kode)
    {
        $start = Carbon::parse($this->filterBulan)->startOfMonth();
        $end   = Carbon::parse($this->filterBulan)->endOfMonth();

        return JurnalUmum::where('no_akun', $kode)
            ->whereBetween('tgl', [$start, $end])
            ->orderBy('tgl', 'asc')
            ->orderBy('jurnal', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    // ── Saldo rekursif berdasarkan saldo_normal akun ─────────────────────────
    public function getTotalRecursive($akun): float
    {
        $total = 0.0;

        $kode = $akun->kode_anak_akun ?? $akun->kode_sub_anak_akun ?? null;

        if ($kode && isset($this->saldoMap[$kode])) {
            $saldoAwal   = (float) ($this->saldoAwalMap[$kode] ?? 0);
            $debit       = (float) ($this->saldoMap[$kode]['d'] ?? 0);
            $kredit      = (float) ($this->saldoMap[$kode]['k'] ?? 0);

            $saldoNormal = strtolower($akun->saldo_normal ?? 'debit');
            $isKredit    = in_array($saldoNormal, ['kredit', 'credit', 'k']);

            if ($isKredit) {
                // Akun kredit: saldo naik jika kredit, turun jika debit
                $total += $saldoAwal + $kredit - $debit;
            } else {
                // Akun debit: saldo naik jika debit, turun jika kredit
                $total += $saldoAwal + $debit - $kredit;
            }
        } elseif ($kode) {
            // Tidak ada mutasi bulan ini, hanya saldo awal
            $total += (float) ($this->saldoAwalMap[$kode] ?? 0);
        }

        if (isset($akun->children)) {
            foreach ($akun->children as $child) {
                $total += $this->getTotalRecursive($child);
            }
        }

        if (isset($akun->subAnakAkuns)) {
            foreach ($akun->subAnakAkuns as $sub) {
                $total += $this->getTotalRecursive($sub);
            }
        }

        return $total;
    }
}