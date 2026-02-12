<?php

namespace App\Services\Penjualans;

use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Illuminate\Support\Facades\DB;

class SyncPenjualanService
{
    /**
     * Kalkulasi total dari detail_penjualans (Return float untuk decimal 15,2)
     */
    public static function calculateCurrentTotal(int $penjualanId): float
    {
        return (float) DetailPenjualan::where('penjualan_id', $penjualanId)
            ->sum('subtotal');
    }

    /**
     * Kalkulasi kembalian awal saat modal dibuka
     */
    public static function calculateKembalian(int $penjualanId, float $total_kemarin): float
    {
        $total_hari_ini = self::calculateCurrentTotal($penjualanId);

        // Jika harga turun, kembalikan selisihnya ke pelanggan
        if ($total_kemarin > $total_hari_ini) {
            return $total_kemarin - $total_hari_ini;
        }

        return 0;
    }

    /**
     * Proses Sinkronisasi ke Database
     */
    public static function syncPenjualan(int $penjualanId, array $data): void
    {
        DB::transaction(function () use ($penjualanId, $data) {
            $penjualan = Penjualan::findOrFail($penjualanId);

            $penjualan->update([
                'total' => $data['total'],
                'bayar' => $data['bayar'],
                'kembalian' => $data['kembalian'],
                // 'metode_pembayaran' => strtoupper($data['metode_bayar']),
                'keterangan_pembayaran' => $data['keterangan'],
            ]);
            
            // Logika tambahan seperti input kas keluar jika ada kembalian 
            // bisa ditambahkan di sini.
        });
    }
}