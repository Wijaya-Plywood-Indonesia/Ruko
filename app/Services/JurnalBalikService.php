<?php

namespace App\Services;

use App\Models\JurnalPembantuHeader;
use App\Models\JurnalPembantuItem;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk membuat Jurnal Balik otomatis.
 *
 * Dipanggil saat Batal Validasi pada penjualan yang sudah LUNAS.
 *
 * PRINSIP JURNAL BALIK:
 * ─────────────────────
 * • Jurnal asli TIDAK dihapus — tetap ada di DB sebagai histori.
 * • Dibuat entri baru dengan D↔K dibalik (map 'd' ↔ 'k').
 * • Jurnal balik mendapat no_jurnal BARU.
 * • Setiap header balik punya:
 *     - adalah_jurnal_balik = true
 *     - membalik_id         = id header asli yang dibalik
 * • Jurnal asli di-update status → 'dibalik'.
 * • Items jurnal balik = salinan items asli (qty & harga sama).
 *
 * CONTOH:
 * ─────────────────────────────────────────────────────────────
 *  Jurnal Asli (no_jurnal=5):        Jurnal Balik (no_jurnal=6):
 *  D 1121-00 Kas Tunai Mut    →      K 1121-00 Kas Tunai Mut
 *  K 4100-02 Penjualan Kiloan →      D 4100-02 Penjualan Kiloan
 *  D 6000-01 HPP Telor        →      K 6000-01 HPP Telor
 *  K 1412-00 Persediaan Kilo  →      D 1412-00 Persediaan Kilo
 */
class JurnalBalikService
{
    /**
     * Buat jurnal balik dari semua jurnal yang terkait dengan no_dokumen (no_nota).
     * Dipanggil dari action Batal Validasi.
     *
     * @param  string  $noDokumen  no_nota penjualan yang dibatalkan
     * @param  int     $userId     user yang melakukan pembatalan
     */
    public function buatJurnalBalikDariNota(string $noDokumen, int $userId): void
    {
        // Ambil semua header jurnal asli dari nota ini
        // yang belum dibalik (status bukan 'dibalik') dan bukan jurnal balik
        $headersAsli = JurnalPembantuHeader::where('no_dokumen', $noDokumen)
            ->where('adalah_jurnal_balik', false)
            ->where('status', '!=', JurnalPembantuHeader::STATUS_DIBALIK)
            ->whereIn('modul_asal', ['pembelian_barang', 'penjualan_telur'])
            ->get();

        if ($headersAsli->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($headersAsli, $userId) {

            // Kelompokkan header asli per no_jurnal
            // Setiap no_jurnal asli → 1 no_jurnal balik baru
            $perNoJurnal = $headersAsli->groupBy('jurnal');

            foreach ($perNoJurnal as $noJurnalAsli => $headers) {

                // Buat no_jurnal baru untuk jurnal balik ini
                $noJurnalBalik = $this->nextNomorJurnal();

                foreach ($headers as $headerAsli) {

                    // Load items asli
                    $headerAsli->load('items');

                    // Buat header balik — D↔K dibalik
                    $headerBalik = JurnalPembantuHeader::create([
                        'no_jurnal_pembantu'  => $this->nextNomorPembantu(),
                        'tgl_transaksi'       => now()->toDateString(),
                        'jenis_transaksi'     => $headerAsli->jenis_transaksi,
                        'modul_asal'          => $headerAsli->modul_asal,
                        'jurnal'              => $noJurnalBalik,
                        'no_akun'             => $headerAsli->no_akun,
                        'nama_akun'           => $headerAsli->nama_akun,
                        'map'                 => $headerAsli->map === 'd' ? 'k' : 'd', // ← DIBALIK
                        'keterangan'          => 'BALIK: ' . $headerAsli->keterangan,
                        'no_dokumen'          => $headerAsli->no_dokumen,
                        'catatan_internal'    => 'Jurnal balik atas pembatalan validasi nota ' . $headerAsli->no_dokumen,
                        'total_nilai'         => 0, // dihitung ulang oleh observer saat items disimpan
                        'status'              => JurnalPembantuHeader::STATUS_DRAFT,
                        'adalah_jurnal_balik' => true,
                        'membalik_id'         => $headerAsli->id,
                        'dibuat_oleh'         => $userId,
                    ]);

                    // Salin semua items dari header asli ke header balik
                    $urut = 1;
                    foreach ($headerAsli->items as $itemAsli) {
                        JurnalPembantuItem::create([
                            'jurnal_pembantu_header_id' => $headerBalik->id,
                            'urut'                      => $urut++,
                            'jenis_pihak'               => $itemAsli->jenis_pihak,
                            'nama_pihak'                => $itemAsli->nama_pihak,
                            'nama_barang'               => $itemAsli->nama_barang,
                            'no_dokumen'                => $itemAsli->no_dokumen,
                            'no_referensi'              => $itemAsli->no_referensi,
                            'keterangan'                => 'BALIK: ' . $itemAsli->keterangan,
                            'banyak'                    => $itemAsli->banyak,
                            'harga'                     => $itemAsli->harga,
                            // jumlah dihitung otomatis oleh Observer di JurnalPembantuItem
                            'status'                    => true,
                            'created_by'                => $userId,
                            'updated_by'                => $userId,
                        ]);
                    }

                    // Tandai header asli sebagai sudah dibalik
                    $headerAsli->update([
                        'status'    => JurnalPembantuHeader::STATUS_DIBALIK,
                        'diubah_oleh' => $userId,
                    ]);
                }
            }
        });
    }

    // ── Helper sequence ───────────────────────────────────────────────────────

    private function nextNomorJurnal(): int
    {
        $max = JurnalPembantuHeader::lockForUpdate()->max('jurnal');
        return ($max ?? 0) + 1;
    }

    private function nextNomorPembantu(): int
    {
        $max = JurnalPembantuHeader::lockForUpdate()->max('no_jurnal_pembantu');
        return ($max ?? 0) + 1;
    }
}
