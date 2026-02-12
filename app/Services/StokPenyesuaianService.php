<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\StokBarangToko;
use App\Models\StokLog;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
                ->title('Transaksi Lunas')
                ->body("Stok $detail->nama_barang di kurangi sejumlah $detail->qty, sebelumnya $stokSebelum, sesudah $stokSesudah")
                ->success()
                ->send();
        }

    }
    public function validasi_batal_dari_lunas(
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
                ->danger()
                ->send();
        }

    }

    public static function queryBarangByToko(int $tokoId): Builder
    {
        $stokTable = (new StokBarangToko)->getTable();

        return Barang::query()
            ->leftJoin($stokTable, function ($join) use ($stokTable, $tokoId) {
                $join->on("$stokTable.barang_id", '=', 'barangs.id')
                     ->where("$stokTable.toko_id", $tokoId);
            })
            ->where("$stokTable.stok", '>', 0)
            ->select('barangs.*');
    }

    public static function calculate_subtotal(
        float|int|null $hargaJual,
        int|null $qty,
        float|int|null $potongan = 0
    ): float {
        $hargaJual = (float) ($hargaJual ?? 0);
        $qty       = (int) ($qty ?? 0);
        $potongan  = (float) ($potongan ?? 0);

        $subtotal = ($hargaJual * $qty) - $potongan;

        return max($subtotal, 0);
    }

    public static function validateSubtotal(
        float|int|null $hargaJual,
        int|null $qty,
        float|int|null $potongan = 0
    ): void {
        $subtotal = self::calculate_subtotal($hargaJual, $qty, $potongan);

        if ($subtotal <= 0) {

            throw ValidationException::withMessages([
                    'subtotal' => 'Pembelian tidak wajar. Subtotal harus lebih dari 0.',
                ]);
        }
    }
}
