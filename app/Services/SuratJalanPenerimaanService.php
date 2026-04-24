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
    public function terima(
        SuratJalan $suratJalan,
        array $details,
        int $userId
    ): void {

        if (!in_array($suratJalan->status, ['dikirim', 'perjalanan'])) {
            throw new Exception('Status surat jalan tidak valid');
        }

        DB::transaction(function () use ($suratJalan, $details, $userId) {

            $adaSelisih = false;

            foreach ($details as $item) {

                $detail = DetailSuratJalan::lockForUpdate()->find($item['id']);

                if (!$detail) {
                    continue;
                }

                $qtyKirim = (float) $detail->qty_kirim;
                $qtyDiterima = (float) ($item['qty_diterima'] ?? 0);

                if ($qtyDiterima > $qtyKirim) {
                    throw new Exception(
                        'Qty diterima melebihi qty kirim untuk barang ID: ' . $detail->barang_id
                    );
                }

                if ($qtyDiterima < $qtyKirim) {
                    $adaSelisih = true;
                }

                if ($qtyDiterima <= 0) {
                    continue;
                }

                // ======================
                // STOK ASAL (KURANG)
                // ======================
                $stokAsal = StokBarangToko::lockForUpdate()
                    ->where('barang_id', $detail->barang_id)
                    ->where('toko_id', $suratJalan->toko_asal_id)
                    ->first();

                if (!$stokAsal) {
                    throw new Exception('Stok asal tidak ditemukan');
                }

                if ($stokAsal->stok < $qtyDiterima) {
                    throw new Exception('Stok asal tidak mencukupi');
                }

                $stokAsalAwal = $stokAsal->stok;
                $stokAsal->decrement('stok', $qtyDiterima);

                StokLog::create([
                    'barang_id' => $detail->barang_id,
                    'toko_id' => $suratJalan->toko_asal_id,
                    'tipe' => 'mutasi_keluar',
                    'qty' => $qtyDiterima,
                    'stok_sebelum' => $stokAsalAwal,
                    'stok_sesudah' => $stokAsal->stok,
                    'referensi_type' => 'surat_jalan',
                    'referensi_id' => $suratJalan->id,
                    'created_by' => $userId,
                ]);

                // ======================
                // STOK TUJUAN (TAMBAH)
                // ======================
                $stokTujuan = StokBarangToko::lockForUpdate()
                    ->firstOrCreate(
                        [
                            'barang_id' => $detail->barang_id,
                            'toko_id' => $suratJalan->toko_tujuan_id,
                        ],
                        ['stok' => 0]
                    );

                $stokTujuanAwal = $stokTujuan->stok;
                $stokTujuan->increment('stok', $qtyDiterima);

                StokLog::create([
                    'barang_id' => $detail->barang_id,
                    'toko_id' => $suratJalan->toko_tujuan_id,
                    'tipe' => 'mutasi_masuk',
                    'qty' => $qtyDiterima,
                    'stok_sebelum' => $stokTujuanAwal,
                    'stok_sesudah' => $stokTujuan->stok,
                    'referensi_type' => 'surat_jalan',
                    'referensi_id' => $suratJalan->id,
                    'created_by' => $userId,
                ]);

                // ======================
                // UPDATE DETAIL
                // ======================
                $detail->update([
                    'qty_diterima' => $qtyDiterima,
                    'catatan' => $item['catatan']
                        ?? ($qtyDiterima < $qtyKirim ? 'Penerimaan selisih' : null),
                ]);
            }

            // ======================
            // UPDATE HEADER
            // ======================
            $suratJalan->update([
                'status' => $adaSelisih ? 'selisih' : 'diterima',
                'validated_by' => $userId,
            ]);

        });
    }
}
