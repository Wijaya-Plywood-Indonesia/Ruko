<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use App\Models\IdentitasToko;
use App\Models\JurnalUmum;
use Filament\Pages\Page;
use UnitEnum;

class StokMatrix extends Page
{
    protected string $view = 'filament.pages.stok-matrix';
    protected static string|UnitEnum|null $navigationGroup = 'Matrix Barang';
    public static ?string $navigationLabel = 'Matrix Barang';

    protected $tokos;
    protected $barangs;
    protected $stok;
    protected int $pairs = 5;

    public function mount(): void
    {
        $this->tokos = IdentitasToko::orderBy('nama_toko')->get();

        $this->barangs = Barang::with('subAnakAkun')->orderBy('nama_barang')->get();

        $matrixTemporaryStok = [];

        foreach ($this->barangs as $barang) {
            $subAkun = $barang->subAnakAkun;
            $kodeAkun = $subAkun?->kode_sub_anak_akun;
            $namaAkun = $subAkun?->nama_sub_anak_akun;

            if (!$kodeAkun) {
                continue;
            }

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

            $tokoId = $this->resolveTokoIdFromAccountName($namaAkun);

            $matrixTemporaryStok[$tokoId][$barang->id] = (object) [
                'stok' => $totalQty
            ];
        }

        $this->stok = collect($matrixTemporaryStok);
    }

    /**
     */
    private function resolveTokoIdFromAccountName(?string $namaAkun): int
    {
        if (empty($namaAkun)) {
            return 1;
        }

        $namaLower = strtolower($namaAkun);

        if (str_contains($namaLower, 'kandang')) {
            return 1;
        }
        if (str_contains($namaLower, 'pusat') || str_contains($namaLower, 'gudang')) {
            return 2;
        }
        if (str_contains($namaLower, 'retail') || str_contains($namaLower, 'toko')) {
            return 3;
        }

        return 1;
    }

    protected function getViewData(): array
    {
        $chunks = $this->barangs->chunk($this->pairs);

        return [
            'tokos'   => $this->tokos,
            'barangs' => $this->barangs,
            'stok'    => $this->stok,
            'pairs'   => $this->pairs,
            'chunks'  => $chunks,
        ];
    }
}
