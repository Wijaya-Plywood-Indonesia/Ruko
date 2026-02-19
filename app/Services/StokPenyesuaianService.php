<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\ReturnPenjualanDetail;
use App\Models\StokBarangToko;
use App\Models\StokLog;
use App\Services\StokLogs\StokLogService;
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
                select('id', 'stok', 'toko_id')
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
            StokLogService::buatLog(
                barangId: $detail->barang_id,
                tokoId: $barang->toko_id,
                tipe: 'penjualan',
                qty: $detail->qty,
                refType: "penjualans",
                refId: $id_penjualan,
                stokTerakhir: $stokSebelum,
                stokSesudah: $stokSesudah
            );
            Notification::make()
                ->title("Stok $detail->nama_barang berhasil dikurangi")
                // ->body("Stok $detail->nama_barang di kurangi sejumlah $detail->qty, sebelumnya $stokSebelum, sesudah $stokSesudah")
                ->body("Total Stok menjadi $stokSesudah")
                ->success()
                ->send();


        }

        Notification::make()
            ->title('Transaksi Lunas')
            ->success()
            ->send();


    }

    public function selesai(
        int $id_penjualan
    ) {
    $penjualanDetails = ReturnPenjualanDetail::where('id_return', $id_penjualan)
            ->select(['id_barang', 'qty', "nama_barang"])
            ->get();

        foreach ($penjualanDetails as $detail) {
            $barang = StokBarangToko::
                select('id', 'stok', 'toko_id')
                ->find($detail->id_barang)
            ;

            if (!$barang) {
                return; // atau throw exception
            }
            $stokSebelum = (int) $barang->stok;
            $stokSesudah = $stokSebelum - (int) $detail->qty;

            $barang->update([
                'stok' => $stokSesudah,
            ]);
            StokLogService::buatLog(
                barangId: $detail->id_barang,
                tokoId: $barang->toko_id,
                tipe: 'retur',
                qty: $detail->qty,
                refType: "penjualan_return",
                refId: $id_penjualan,
                stokTerakhir: $stokSebelum,
                stokSesudah: $stokSesudah
            );
            Notification::make()
                ->title("Stok $detail->nama_barang berhasil dikurangi")
                // ->body("Stok $detail->nama_barang di kurangi sejumlah $detail->qty, sebelumnya $stokSebelum, sesudah $stokSesudah")
                ->body("Total Stok menjadi $stokSesudah")
                ->success()
                ->send();


        }

        Notification::make()
            ->title('Return Selesai')
            ->success()
            ->send();


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
                select('id', 'stok', 'toko_id')
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
            StokLogService::buatLog(
                barangId: $detail->barang_id,
                tokoId: $barang->toko_id,
                tipe: 'penjualan',
                qty: $detail->qty,
                refType: "penjualans",
                refId: $id_penjualan,
                stokTerakhir: $stokSebelum,
                stokSesudah: $stokSesudah
            );

            Notification::make()
                ->title("Stok $detail->nama_barang berhasil di kembalikan")
                ->body("Total Stok menjadi $stokSesudah")
                ->success()
                ->send();
            }
        Notification::make()
            ->title('Transaksi dibatalkan')
            ->success()
            ->send();

    }
    public function validasi_batal_dari_selesai(
        int $id_penjualan
    ) {
        $penjualanDetails = ReturnPenjualanDetail::where('id_return', $id_penjualan)
            ->select(['id_barang', 'qty', "nama_barang"])
            ->get();

        foreach ($penjualanDetails as $detail) {
            $barang = StokBarangToko::
                select('id', 'stok', 'toko_id')
                ->find($detail->id_barang)
            ;

            if (!$barang) {
                return; // atau throw exception
            }
            $stokSebelum = (int) $barang->stok;
            $stokSesudah = $stokSebelum + (int) $detail->qty;

            $barang->update([
                'stok' => $stokSesudah,
            ]);
            StokLogService::buatLog(
                barangId: $detail->id_barang,
                tokoId: $barang->toko_id,
                tipe: 'retur',
                qty: $detail->qty,
                refType: "penjualan_return",
                refId: $id_penjualan,
                stokTerakhir: $stokSebelum,
                stokSesudah: $stokSesudah
            );

            Notification::make()
                ->title("Stok $detail->nama_barang berhasil di kembalikan")
                ->body("Total Stok menjadi $stokSesudah")
                ->success()
                ->send();
            }
        Notification::make()
            ->title('Return dibatalkan')
            ->success()
            ->send();

    }

    public static function queryBarangByToko(int $tokoId, int $penjualanId): Builder
    {
        return Barang::query()
            ->whereHas('stokBarangTokos', function ($query) use ($tokoId) {
                $query->where('toko_id', $tokoId)
                    ->where('stok', '>', 0);
            })
            ->whereDoesntHave('penjualanDetails', function ($query) use ($penjualanId) {
                $query->where('penjualan_id', $penjualanId);
            })
            ->select('barangs.*');
    }

    public static function calculate_subtotal(
        float|int|null $hargaJual,
        int|null $qty,
        float|int|null $potongan = 0
    ): float {
        $hargaJual = (float) ($hargaJual ?? 0);
        $qty = (int) ($qty ?? 0);
        $potongan = (float) ($potongan ?? 0);

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
