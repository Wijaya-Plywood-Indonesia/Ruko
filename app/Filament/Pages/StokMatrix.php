<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use App\Models\JurnalUmum;
use Filament\Pages\Page;
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
        $this->barangs = Barang::with('subAnakAkun')
            ->whereHas('subAnakAkun', function ($query) {
                $query->whereNotNull('kode_sub_anak_akun')
                    ->where('kode_sub_anak_akun', '!=', '');
            })
            ->orderBy('nama_barang')
            ->get();

        $matrixTemporaryStok = [];

        foreach ($this->barangs as $barang) {
            $subAkun = $barang->subAnakAkun;
            $kodeAkun = $subAkun->kode_sub_anak_akun;

            $transaksis = JurnalUmum::where('no_akun', $kodeAkun)->get();
            $totalQty = 0.0;

            foreach ($transaksis as $trx) {
                $isDebit = in_array(strtolower($trx->map), ['d', 'debit']);
                $qty = (float) ($trx->banyak ?? 0);

                if ($qty > 0) {
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
        $chunks = $this->barangs->chunk($this->pairs);

        return [
            'barangs' => $this->barangs,
            'stok'    => $this->stok,
            'pairs'   => $this->pairs,
            'chunks'  => $chunks,
        ];
    }
}
