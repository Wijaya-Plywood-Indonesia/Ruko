<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use App\Models\IdentitasToko;
use App\Models\StokBarangToko;
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
    protected int $pairs = 5;   // jumlah kolom pasangan "Barang + Banyak"

    public function mount(): void
    {
        // ambil semua toko
        $this->tokos = IdentitasToko::orderBy('nama_toko')->get();

        // ambil semua barang (urut)
        $this->barangs = Barang::orderBy('nama_barang')->get();

        // ambil stok (digroup berdasarkan toko -> barang)
        $this->stok = StokBarangToko::get()
            ->groupBy('toko_id')
            ->map(fn($rows) => $rows->keyBy('barang_id'));
    }

    /** 
     * Data yang dikirim ke Blade  
     **/
    protected function getViewData(): array
    {
        // Membagi barang ke dalam kelompok (chunks) per N-pairs
        $chunks = $this->barangs->chunk($this->pairs);

        return [
            'tokos' => $this->tokos,
            'barangs' => $this->barangs,
            'stok' => $this->stok,
            'pairs' => $this->pairs,
            'chunks' => $chunks,
        ];
    }
}
