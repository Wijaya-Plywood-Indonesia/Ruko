<?php

namespace App\Services;

use App\Models\SuratJalan;
use App\Models\StokBarangToko;
use App\Models\StokLog;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\DetailSuratJalan;
class SuratJalanPenerimaanService
{
    /**
     * Terima surat jalan
     *
     * @param  SuratJalan  $suratJalan
     * @param  array       $details   // ← dari Livewire
     * @param  int         $userId
     */

    public function terima(
        SuratJalan $suratJalan,
        array $details,
        int $userId
    ): void {
        // 🔒 proteksi status
        if ($suratJalan->status !== 'dikirim') {
            throw new Exception('Status surat jalan tidak valid');
        }

        DB::transaction(function () use ($suratJalan, $details, $userId) {

            $adaSelisih = false;

            foreach ($details as $item) {

                /** @var DetailSuratJalan $detail */
                $detail = DetailSuratJalan::lockForUpdate()->find($item['id']);

                if (!$detail) {
                    continue;
                }

                $qtyKirim = (int) $detail->qty_kirim;
                $qtyDiterima = (int) ($item['qty_diterima'] ?? 0);

                // 🔒 validasi keras
                if ($qtyDiterima > $qtyKirim) {
                    throw new Exception(
                        'Qty diterima melebihi qty kirim untuk barang ID: ' . $detail->barang_id
                    );
                }

                if ($qtyDiterima < $qtyKirim) {
                    $adaSelisih = true;
                }

                // skip jika tidak ada barang diterima
                if ($qtyDiterima <= 0) {
                    continue;
                }

                // =========================
                // STOK TOKO TUJUAN
                // =========================
                $stok = StokBarangToko::firstOrCreate(
                    [
                        'barang_id' => $detail->barang_id,
                        'toko_id' => $suratJalan->toko_tujuan_id,
                    ],
                    ['stok' => 0]
                );

                $stokAwal = $stok->stok;
                $stok->increment('stok', $qtyDiterima);

                // =========================
                // LOG STOK
                // =========================
                StokLog::create([
                    'barang_id' => $detail->barang_id,
                    'toko_id' => $suratJalan->toko_tujuan_id,
                    'tipe' => 'mutasi_masuk',
                    'qty' => $qtyDiterima,
                    'stok_sebelum' => $stokAwal,
                    'stok_sesudah' => $stok->stok,
                    'referensi_type' => 'surat_jalan',
                    'referensi_id' => $suratJalan->id,
                    'created_by' => $userId,
                ]);

                // =========================
                // UPDATE DETAIL SJ
                // =========================
                $detail->update([
                    'qty_diterima' => $qtyDiterima,
                    'catatan' => $item['catatan']
                        ?? ($qtyDiterima < $qtyKirim ? 'Penerimaan selisih' : null),
                ]);
            }

            // =========================
            // UPDATE HEADER SJ
            // =========================
            $suratJalan->update([
                'status' => $adaSelisih ? 'selisih' : 'diterima',
                'validated_by' => $userId,
            ]);
        });
    }
    // public function terima(SuratJalan $suratJalan, array $details, int $userId): void
    // {
    //     if ($suratJalan->status !== 'dikirim') {
    //         throw new Exception('Status surat jalan tidak valid');
    //     }

    //     DB::transaction(function () use ($suratJalan, $details, $userId) {

    //         foreach ($details as $detail) {

    //             $qtyDiterima = (int) ($detail['qty_diterima'] ?? 0);

    //             // skip jika 0 / kosong
    //             if ($qtyDiterima <= 0) {
    //                 continue;
    //             }

    //             // ambil / buat stok toko tujuan
    //             $stok = StokBarangToko::firstOrCreate(
    //                 [
    //                     'barang_id' => $detail['barang_id'] ?? $detail['id'] ?? null,
    //                     'toko_id' => $suratJalan->toko_tujuan_id,
    //                 ],
    //                 ['stok' => 0]
    //             );

    //             $stokAwal = $stok->stok;
    //             $stok->stok += $qtyDiterima;
    //             $stok->save();

    //             // log stok
    //             StokLog::create([
    //                 'barang_id' => $stok->barang_id,
    //                 'toko_id' => $suratJalan->toko_tujuan_id,
    //                 'tipe' => 'mutasi_masuk',
    //                 'qty' => $qtyDiterima,
    //                 'stok_sebelum' => $stokAwal,
    //                 'stok_sesudah' => $stok->stok,
    //                 'referensi_type' => 'surat_jalan',
    //                 'referensi_id' => $suratJalan->id,
    //                 'created_by' => $userId,
    //             ]);

    //             // update detail surat jalan
    //             $suratJalan->details()
    //                 ->where('id', $detail['id'])
    //                 ->update([
    //                     'qty_diterima' => $qtyDiterima,
    //                     'catatan' => $detail['catatan'] ?? null,
    //                 ]);
    //         }

    //         // update header surat jalan
    //         $suratJalan->update([
    //             'status' => 'diterima',
    //             'validated_by' => $userId,
    //         ]);
    //     });
    // }
}
