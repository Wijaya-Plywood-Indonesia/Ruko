<?php

namespace App\Services;

use App\Models\StokBarangToko;
use App\Models\StokLog;
use Filament\Notifications\Notification;
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
    public function lunas(
        int $id_penjualan
    ) {
        $penjualanDetails = DB::table('penjualan_details')
            ->where('penjualan_id', $id_penjualan)
            ->select(['barang_id', 'qty', "nama_barang"])
            ->get();

        foreach ($penjualanDetails as $detail) {
            $barang = StokBarangToko::
                select('id', 'stok')
                ->find($detail->barang_id)
            ;

            if (!$barang) {
                return; // atau throw exception
            }
            $stokSebelum = (int) $barang->stok;
            $stokSesudah = $stokSebelum - (int) $detail->qty;

            $barang->update([
                'stok' => $stokSesudah,
            ]);
            Notification::make()
                ->title('Transaksi berhasil dibatalkan')
                ->body("Stok $detail->nama_barang di kembalikan sejumlah $detail->qty, sebelumnya $stokSebelum, sesudah $stokSesudah")
                ->success()
                ->send();
        }

    }
    public function dibatalkan(
        int $id_penjualan
    ) {
        $penjualanDetails = DB::table('penjualan_details')
            ->where('penjualan_id', $id_penjualan)
            ->select(['barang_id', 'qty', "nama_barang"])
            ->get();

        foreach ($penjualanDetails as $detail) {
            $barang = StokBarangToko::
                select('id', 'stok')
                ->find($detail->barang_id)
            ;

            if (!$barang) {
                return; // atau throw exception
            }
            $stokSebelum = (int) $barang->stok;
            $stokSesudah = $stokSebelum + (int) $detail->qty;

            $barang->update([
                'stok' => $stokSesudah,
            ]);
            Notification::make()
                ->title('Transaksi berhasil dibatalkan')
                ->body("Stok $detail->nama_barang di kembalikan sejumlah $detail->qty, sebelumnya $stokSebelum, sesudah $stokSesudah")
                ->success()
                ->send();
        }

    }
}
