<?php

namespace App\Services;

use App\Models\StokBarangToko;
use App\Models\StokLog;
use Illuminate\Support\Facades\DB;

class StokPenyesuaianService
{
    public function sesuaikan(
        int $barangId,
        int $tokoId,
        int $stokFisik,
        int $userId,
        ?string $catatan
    ): void {
        DB::transaction(function () use ($barangId, $tokoId, $stokFisik, $userId, $catatan) {

            $stok = StokBarangToko::lockForUpdate()->firstOrCreate(
                [
                    'barang_id' => $barangId,
                    'toko_id' => $tokoId,
                ],
                ['stok' => 0]
            );

            $stokSebelum = $stok->stok;

            // kalau tidak ada perubahan → stop
            if ($stokSebelum === $stokFisik) {
                return;
            }

            $stok->update([
                'stok' => $stokFisik,
            ]);

            StokLog::create([
                'barang_id' => $barangId,
                'toko_id' => $tokoId,
                'tipe' => 'penyesuaian',
                'qty' => $stokFisik - $stokSebelum,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokFisik,
                'referensi_type' => 'stok_opname',
                'created_by' => $userId,
            ]);
        });
    }
}
