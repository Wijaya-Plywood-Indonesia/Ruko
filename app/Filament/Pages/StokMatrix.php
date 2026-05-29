<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use App\Models\JurnalUmum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class StokMatrix extends Page
{
    protected string $view = 'filament.pages.stok-matrix';
    protected static string|UnitEnum|null $navigationGroup = 'Matrix Barang';
    public static ?string $navigationLabel = 'Matrix Barang';

    protected $barangs;
    protected $stok;
    protected int $pairs = 5;

    public function mount(): void
    {
        $this->barangs = Barang::with(['subAnakAkun', 'satuan', 'kategori'])
            ->whereHas('subAnakAkun', function ($query) {
                $query->whereNotNull('kode_sub_anak_akun')
                    ->where('kode_sub_anak_akun', '!=', '');
            })
            ->orderBy('nama_barang')
            ->get();

        $kodeAkuns = $this->barangs->map(function ($barang) {
            return $barang->subAnakAkun?->kode_sub_anak_akun;
        })->filter()->unique()->toArray();

        $transaksisGrouped = JurnalUmum::select('no_akun', 'map', DB::raw('SUM(COALESCE(banyak, 0)) as total_qty'))
            ->whereIn('no_akun', $kodeAkuns)
            ->groupBy('no_akun', 'map')
            ->get()
            ->groupBy('no_akun');

        $matrixTemporaryStok = [];

        foreach ($this->barangs as $barang) {
            $subAkun = $barang->subAnakAkun;
            $kodeAkun = $subAkun?->kode_sub_anak_akun;

            $totalQty = 0.0;
            if ($kodeAkun && isset($transaksisGrouped[$kodeAkun])) {
                foreach ($transaksisGrouped[$kodeAkun] as $trx) {
                    $isDebit = in_array(strtolower($trx->map), ['d', 'debit']);
                    $qty = (float) $trx->total_qty;

                    if ($isDebit) {
                        $totalQty += $qty;
                    } else {
                        $totalQty -= $qty;
                    }
                }
            }

            $matrixTemporaryStok[$barang->id] = (object) [
                'stok' => $totalQty
            ];
        }

        $this->stok = collect($matrixTemporaryStok);
    }

    protected function getViewData(): array
    {
        $totalBarang = $this->barangs->count();
        $totalStokAktifCount = 0;
        $totalStokKosongCount = 0;
        $totalAkumulasiStok = 0.0;

        foreach ($this->barangs as $barang) {
            $qty = $this->stok[$barang->id]->stok ?? 0.0;
            if ($qty > 0) {
                $totalStokAktifCount++;
                $totalAkumulasiStok += $qty;
            } else {
                $totalStokKosongCount++;
            }
        }

        $chunks = $this->barangs->chunk($this->pairs);

        return [
            'barangs'              => $this->barangs,
            'stok'                 => $this->stok,
            'pairs'                => $this->pairs,
            'chunks'               => $chunks,
            'totalBarang'          => $totalBarang,
            'totalStokAktifCount'  => $totalStokAktifCount,
            'totalStokKosongCount' => $totalStokKosongCount,
            'totalAkumulasiStok'   => $totalAkumulasiStok,
        ];
    }
}

