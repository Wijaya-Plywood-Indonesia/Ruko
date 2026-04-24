<?php

namespace App\Services;

use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\StokBarangToko;
use App\Models\StokLog;
use Illuminate\Support\Facades\DB;
use Exception;

class StockOpnameService
{
    /* =========================
     |  SUBMIT KE APPROVAL
     ========================= */

    /**
     * Ubah status dari draft → menunggu.
     *
     * Aturan stok aktual kosong:
     * Jika petugas tidak mengisi stok_aktual pada suatu barang,
     * dianggap stok aktual = stok sistem (tidak ada selisih).
     * Ini mendorong petugas hanya mengisi barang yang memang ada perbedaan.
     */
    public function submitUntukApproval(StockOpname $opname, int $userId): void
    {
        if (!$opname->isDraft()) {
            throw new Exception('Hanya opname berstatus draft yang bisa disubmit.');
        }

        DB::transaction(function () use ($opname) {
            foreach ($opname->details as $detail) {

                // Jika tidak diisi → anggap sama dengan stok sistem (selisih = 0)
                $stokAktual = $detail->stok_aktual !== null
                    ? (float) $detail->stok_aktual
                    : (float) $detail->stok_sistem;

                $detail->update([
                    'stok_aktual' => $stokAktual,
                    'selisih' => $stokAktual - (float) $detail->stok_sistem,
                ]);
            }

            $opname->update(['status' => 'menunggu']);
        });
    }

    /* =========================
     |  APPROVE + ADJUST STOK
     ========================= */

    /**
     * Approve opname: ubah status → disetujui dan adjust semua stok.
     * Hanya barang yang ada selisih yang dibuatkan log adjustment.
     */
    public function approve(StockOpname $opname, int $approverId, ?string $catatanApproval = null): void
    {
        if (!$opname->isMenunggu()) {
            throw new Exception('Hanya opname berstatus menunggu yang bisa disetujui.');
        }

        DB::transaction(function () use ($opname, $approverId, $catatanApproval) {

            foreach ($opname->details as $detail) {

                // Lewati barang yang tidak ada selisih — tidak perlu adjust
                if ((float) $detail->selisih == 0) {
                    continue;
                }

                $stok = StokBarangToko::lockForUpdate()
                    ->where('barang_id', $detail->barang_id)
                    ->where('toko_id', $opname->toko_id)
                    ->first();

                if (!$stok) {
                    $stok = StokBarangToko::create([
                        'barang_id' => $detail->barang_id,
                        'toko_id' => $opname->toko_id,
                        'stok' => 0,
                    ]);
                }

                $stokSebelum = (float) $stok->stok;
                $stokSesudah = (float) $detail->stok_aktual;

                $stok->update(['stok' => $stokSesudah]);

                StokLog::create([
                    'barang_id' => $detail->barang_id,
                    'toko_id' => $opname->toko_id,
                    'tipe' => 'adjustment',
                    'qty' => abs($detail->selisih),
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $stokSesudah,
                    'referensi_type' => 'stock_opname',
                    'referensi_id' => $opname->id,
                    'created_by' => $approverId,
                ]);
            }

            $opname->update([
                'status' => 'disetujui',
                'approved_by' => $approverId,
                'approved_at' => now(),
                'catatan_approval' => $catatanApproval,
            ]);
        });
    }

    /* =========================
     |  TOLAK
     ========================= */

    public function tolak(StockOpname $opname, int $approverId, ?string $catatanApproval = null): void
    {
        if (!$opname->isMenunggu()) {
            throw new Exception('Hanya opname berstatus menunggu yang bisa ditolak.');
        }

        // Set kembali ke draft agar petugas bisa revisi dan submit ulang
        $opname->update([
            'status' => 'ditolak',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'catatan_approval' => $catatanApproval,
        ]);
    }
}