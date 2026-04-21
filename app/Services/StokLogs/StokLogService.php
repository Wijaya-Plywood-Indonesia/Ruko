<?php

namespace App\Services\StokLogs;

use App\Models\StokLog;
use Illuminate\Support\Facades\DB;

class StokLogService
{
    public static function buatLog(
        int $barangId,
        int $tokoId,
        string $tipe,
        float $qty,
        string $refType,
        int $refId,
        float $stokTerakhir,
        float $stokSesudah
    ) {
        // Gunakan Transaction agar stok_sesudah akurat jika ada request bersamaan
        return StokLog::create([
            'barang_id' => $barangId,
            'toko_id' => $tokoId,
            'tipe' => $tipe,
            'qty' => $qty,
            'stok_sebelum' => $stokTerakhir,
            'stok_sesudah' => $stokSesudah,
            'referensi_type' => $refType,
            'referensi_id' => $refId,
            'created_by' => auth()->id(),
        ]);
    }
}