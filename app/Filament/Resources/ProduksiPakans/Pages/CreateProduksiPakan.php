<?php

namespace App\Filament\Resources\ProduksiPakans\Pages;

use App\Filament\Resources\ProduksiPakans\ProduksiPakanResource;
use App\Models\Komposisi;
use App\Models\ProduksiPakanBahan;
use App\Models\ProduksiPakanHasil;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateProduksiPakan extends CreateRecord
{
    protected static string $resource = ProduksiPakanResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record; // Data header produksi yang baru dibuat
        $idKomposisi = $record->id_komposisi;

        // Validasi: Jika tidak ada resep yang dipilih, lewati proses otomatis
        if (!$idKomposisi) {
            return;
        }

        DB::transaction(function () use ($record, $idKomposisi) {
            // 1. Ambil data Master Resep beserta detail komposisinya
            $resep = Komposisi::with('detailKomposisi')->find($idKomposisi);

            if ($resep) {
                $totalKuantitasBahan = 0;

                // 2. AUTO-GENERATE BAHAN BAKU (Input)
                foreach ($resep->detailKomposisi as $detail) {
                    ProduksiPakanBahan::create([
                        'id_produksi_pakan' => $record->id,
                        'id_barang'         => $detail->id_barang,
                        'kuantitas'         => $detail->kuantitas,
                    ]);

                    // Akumulasi total kuantitas dari semua bahan dalam resep
                    $totalKuantitasBahan += $detail->kuantitas;
                }

                // 3. AUTO-GENERATE HASIL PRODUKSI (Output)
                // Kuantitas hasil merupakan akumulasi berat/jumlah dari seluruh bahan baku
                ProduksiPakanHasil::create([
                    'id_produksi_pakan' => $record->id,
                    'id_barang'         => $resep->id_barang, // Produk jadi dari master resep
                    'kuantitas'         => $totalKuantitasBahan,
                ]);
            }
        });
    }
}
