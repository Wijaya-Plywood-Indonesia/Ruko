<?php

namespace App\Services;

use App\Models\SuratJalan;
use App\Models\StokBarangToko;
use App\Models\StokLog;
use Illuminate\Support\Facades\DB;
use Exception;

class SuratJalanPenerimaanService
{
    public function terima(SuratJalan $suratJalan, int $userId): void
    {
        if ($suratJalan->status !== 'dikirim') {
            throw new Exception('Status surat jalan tidak valid');
        }

        DB::transaction(function () use ($suratJalan, $userId) {

            foreach ($suratJalan->details as $detail) {

                if (!$detail->qty_diterima || $detail->qty_diterima <= 0) {
                    continue;
                }

                $stok = StokBarangToko::firstOrCreate(
                    [
                        'barang_id' => $detail->barang_id,
                        'toko_id' => $suratJalan->toko_tujuan_id,
                    ],
                    ['stok' => 0]
                );

                $stokAwal = $stok->stok;
                $stok->stok += $detail->qty_diterima;
                $stok->save();

                StokLog::create([
                    'barang_id' => $detail->barang_id,
                    'toko_id' => $suratJalan->toko_tujuan_id,
                    'tipe' => 'mutasi_masuk',
                    'qty' => $detail->qty_diterima,
                    'stok_sebelum' => $stokAwal,
                    'stok_sesudah' => $stok->stok,
                    'referensi_type' => 'surat_jalan',
                    'referensi_id' => $suratJalan->id,
                    'created_by' => $userId,
                ]);
            }

            $suratJalan->update([
                'status' => 'diterima',
                'validated_by' => $userId,
            ]);
        });
    }
}
