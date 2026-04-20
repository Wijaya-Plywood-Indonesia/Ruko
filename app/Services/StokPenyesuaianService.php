<?php

namespace App\Services;

use App\Models\Barang;
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

            $stok = StokBarangToko::where('barang_id', $barangId)
                ->where('toko_id', $tokoId)
                ->lockForUpdate()
                ->first();

            if (!$stok) {
                $stok = StokBarangToko::create([
                    'barang_id' => $barangId,
                    'toko_id' => $tokoId,
                    'stok' => 0,
                ]);
            }

            $stokSebelum = $stok->stok;

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

    public function lunas(int $id_penjualan): void
    {
        DB::transaction(function () use ($id_penjualan) {

            $tokoId = DB::table('penjualans')
                ->where('id', $id_penjualan)
                ->value('toko_id');

            $details = DB::table('penjualan_details')
                ->where('penjualan_id', $id_penjualan)
                ->select(['barang_id', 'qty', 'nama_barang'])
                ->get();

            foreach ($details as $detail) {

                $stok = StokBarangToko::where('barang_id', $detail->barang_id)
                    ->where('toko_id', $tokoId)
                    ->lockForUpdate()
                    ->first();

                if (!$stok) {
                    throw ValidationException::withMessages([
                        'stok' => "Stok {$detail->nama_barang} tidak ditemukan"
                    ]);
                }

                $stokSebelum = (int) $stok->stok;
                $stokSesudah = $stokSebelum - (int) $detail->qty;

                if ($stokSesudah < 0) {
                    throw ValidationException::withMessages([
                        'stok' => "Stok {$detail->nama_barang} tidak mencukupi"
                    ]);
                }

                $stok->update([
                    'stok' => $stokSesudah,
                ]);

                StokLogService::buatLog(
                    barangId: $detail->barang_id,
                    tokoId: $tokoId,
                    tipe: 'penjualan',
                    qty: -$detail->qty,
                    refType: "penjualans",
                    refId: $id_penjualan,
                    stokTerakhir: $stokSebelum,
                    stokSesudah: $stokSesudah
                );

                Notification::make()
                    ->title("Stok {$detail->nama_barang} berkurang")
                    ->body("Sisa stok: $stokSesudah")
                    ->success()
                    ->send();
            }

            Notification::make()
                ->title('Transaksi Lunas')
                ->success()
                ->send();
        });
    }

    public function batalLunas(int $id_penjualan): void
    {
        DB::transaction(function () use ($id_penjualan) {

            $tokoId = DB::table('penjualans')
                ->where('id', $id_penjualan)
                ->value('toko_id');

            $details = DB::table('penjualan_details')
                ->where('penjualan_id', $id_penjualan)
                ->select(['barang_id', 'qty', 'nama_barang'])
                ->get();

            foreach ($details as $detail) {

                $stok = StokBarangToko::where('barang_id', $detail->barang_id)
                    ->where('toko_id', $tokoId)
                    ->lockForUpdate()
                    ->first();

                if (!$stok) {
                    throw ValidationException::withMessages([
                        'stok' => "Stok {$detail->nama_barang} tidak ditemukan"
                    ]);
                }

                $stokSebelum = (int) $stok->stok;
                $stokSesudah = $stokSebelum + (int) $detail->qty;

                $stok->update([
                    'stok' => $stokSesudah,
                ]);

                StokLogService::buatLog(
                    barangId: $detail->barang_id,
                    tokoId: $tokoId,
                    tipe: 'batal_penjualan',
                    qty: $detail->qty,
                    refType: "penjualans",
                    refId: $id_penjualan,
                    stokTerakhir: $stokSebelum,
                    stokSesudah: $stokSesudah
                );

                Notification::make()
                    ->title("Stok {$detail->nama_barang} dikembalikan")
                    ->body("Total stok: $stokSesudah")
                    ->success()
                    ->send();
            }

            Notification::make()
                ->title('Transaksi dibatalkan')
                ->success()
                ->send();
        });
    }

    public static function queryBarangByToko(int $tokoId, int $penjualanId): Builder
    {
        return Barang::query()
            ->whereHas('stokBarangTokos', function ($q) use ($tokoId) {
                $q->where('toko_id', $tokoId)
                  ->where('stok', '>', 0);
            })
            ->whereDoesntHave('penjualanDetails', function ($q) use ($penjualanId) {
                $q->where('penjualan_id', $penjualanId);
            });
    }

    public static function calculate_subtotal(
        float|int|null $hargaJual,
        int|null $qty,
        float|int|null $potongan = 0
    ): float {
        $subtotal = ((float)$hargaJual * (int)$qty) - (float)$potongan;
        return max($subtotal, 0);
    }

    public static function validateSubtotal(
        float|int|null $hargaJual,
        int|null $qty,
        float|int|null $potongan = 0
    ): void {
        if (self::calculate_subtotal($hargaJual, $qty, $potongan) <= 0) {
            throw ValidationException::withMessages([
                'subtotal' => 'Subtotal harus lebih dari 0.',
            ]);
        }
    }
}