<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\SubAnakAkun;
use App\Models\JurnalPembantuHeader;
use App\Models\JurnalPembantuItem;
use App\Models\PembelianMetodePembayaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JurnalPembelianService
{
    /** Pemetaan Kode Akun Sesuai Standar Akuntansi Perusahaan */
    const KODE_HUTANG_DAGANG     = '2100-00';
    const KODE_KAS_DEFAULT       = '1121-00';
    const KODE_BANK_INTAN        = '1212-00';

    /** KUNCI PERBAIKAN: Kode akun beban penyeimbang Debit (D) */
    const KODE_BEY_ONGKIR_DEBET  = '5200-07'; // Biaya Angkut Pembelian
    const KODE_BEY_LAIN_DEBET    = '5900-00'; // Beban Lain-lain

    public function buatJurnalDariPembelian(Pembelian $pembelian, int $userId): void
    {
        $pembelian->loadMissing([
            'detailPembelians.barang.subAnakAkun',
            'metodePembayarans'
        ]);

        if ($pembelian->detailPembelians->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($pembelian, $userId) {
            $tgl = $pembelian->tanggal;
            $nota = $pembelian->nomor_nota;
            $supplier = $pembelian->supplier_name ?: 'Supplier';
            $grandTotal = (float) $pembelian->grand_total;

            $noJurnal = JurnalPembantuHeader::lockForUpdate()->max('jurnal') + 1;

            // ──────────────────────────────────────────────────────────────
            // SISI DEBIT (D) 1: Nilai Pokok Barang (Persediaan Gudang)
            // ──────────────────────────────────────────────────────────────
            foreach ($pembelian->detailPembelians as $index => $detail) {
                $barang = $detail->barang;
                $kodeAkunDebet = $barang?->subAnakAkun?->kode_sub_anak_akun;

                if (!$kodeAkunDebet) {
                    Log::warning("[JurnalPembelian] Barang '{$detail->nama_barang}' belum di-set akun jurnalnya. Kebal ke akun default.");
                    $kodeAkunDebet = '1411-00';
                }

                $namaAkunDebet = $this->getNamaAkun($kodeAkunDebet);

                $headerD = JurnalPembantuHeader::create([
                    'no_jurnal_pembantu' => JurnalPembantuHeader::lockForUpdate()->max('no_jurnal_pembantu') + 1,
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'bm',
                    'modul_asal'         => 'pembelian_barang',
                    'jurnal'             => $noJurnal,
                    'no_akun'            => $kodeAkunDebet,
                    'nama_akun'          => $namaAkunDebet,
                    'map'                => 'd',
                    'keterangan'         => "{$detail->nama_barang} | Nota: {$nota}",
                    'no_dokumen'         => $nota,
                    'total_nilai'        => $detail->subtotal,
                    'status'             => JurnalPembantuHeader::STATUS_DRAFT,
                    'dibuat_oleh'        => $userId,
                ]);

                JurnalPembantuItem::create([
                    'jurnal_pembantu_header_id' => $headerD->id,
                    'urut'         => 1,
                    'jenis_pihak'  => 'supplier',
                    'nama_pihak'   => $supplier,
                    'nama_barang'  => $detail->nama_barang,
                    'no_dokumen'   => $nota,
                    'keterangan'   => "Masuk Gudang {$detail->qty} {$detail->satuan}",
                    'banyak'       => $detail->qty,
                    'harga'        => $detail->harga_beli,
                    'jumlah'       => $detail->subtotal,
                    'status'       => true,
                    'created_by'   => $userId,
                ]);
            }

            // ──────────────────────────────────────────────────────────────
            // SISI DEBIT (D) 2: BEBAN ONGKIR & BIAYA LAIN (PENYEIMBANG)
            // ──────────────────────────────────────────────────────────────
            $ongkir = (float) ($pembelian->ongkir ?? 0);
            if ($ongkir > 0) {
                $namaAkunOngkir = $this->getNamaAkun(self::KODE_BEY_ONGKIR_DEBET) ?: 'Biaya Angkut Pembelian';

                $headerOngkirD = JurnalPembantuHeader::create([
                    'no_jurnal_pembantu' => JurnalPembantuHeader::lockForUpdate()->max('no_jurnal_pembantu') + 1,
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'bm',
                    'modul_asal'         => 'pembelian_barang',
                    'jurnal'             => $noJurnal,
                    'no_akun'            => self::KODE_BEY_ONGKIR_DEBET,
                    'nama_akun'          => $namaAkunOngkir,
                    'map'                => 'd',
                    'keterangan'         => "Beban Ongkos Kirim Pembelian | Nota: {$nota}",
                    'no_dokumen'         => $nota,
                    'total_nilai'        => $ongkir,
                    'status'             => JurnalPembantuHeader::STATUS_DRAFT,
                    'dibuat_oleh'        => $userId,
                ]);

                $this->buatItemDetail($headerOngkirD->id, 1, $supplier, $nota, "Alokasi Beban Ongkir Nota {$nota}", $ongkir, $userId);
            }

            $biayaLain = (float) ($pembelian->biaya_lain ?? 0);
            if ($biayaLain > 0) {
                $namaAkunLain = $this->getNamaAkun(self::KODE_BEY_LAIN_DEBET) ?: 'Beban Lain-Lain';

                $headerLainD = JurnalPembantuHeader::create([
                    'no_jurnal_pembantu' => JurnalPembantuHeader::lockForUpdate()->max('no_jurnal_pembantu') + 1,
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'bm',
                    'modul_asal'         => 'pembelian_barang',
                    'jurnal'             => $noJurnal,
                    'no_akun'            => self::KODE_BEY_LAIN_DEBET,
                    'nama_akun'          => $namaAkunLain,
                    'map'                => 'd',
                    'keterangan'         => "Beban Biaya Lain-Lain Pembelian | Nota: {$nota}",
                    'no_dokumen'         => $nota,
                    'total_nilai'        => $biayaLain,
                    'status'             => JurnalPembantuHeader::STATUS_DRAFT,
                    'dibuat_oleh'        => $userId,
                ]);

                $this->buatItemDetail($headerLainD->id, 1, $supplier, $nota, "Alokasi Biaya Lain-Lain Nota {$nota}", $biayaLain, $userId);
            }

            // ──────────────────────────────────────────────────────────────
            // SISI KREDIT (K) : KUNCI EVALUASI BERDASARKAN MODE PEMBAYARAN DI DB
            // ──────────────────────────────────────────────────────────────
            $totalUangMuka = 0;
            $sisaHutang    = 0;

            // Ambil data pembayaran pertama dari DB
            $pembayaranDetail = $pembelian->metodePembayarans->first();
            $methodString = $pembayaranDetail?->payment_method ?? PembelianMetodePembayaran::METODE_TUNAI;

            // ─── LOGIKA UTAMA: EVALUASI BERDASARKAN METODE ───
            if ($methodString === PembelianMetodePembayaran::METODE_TUNAI) {
                // 1. JIKA TUNAI: Ambil nilai Grand Total penuh, sisa hutang = 0
                $totalUangMuka = $grandTotal;
                $sisaHutang    = 0;
            } else {
                // 2. JIKA TRANSFER, CICILAN, ATAU DP: Ambil nominal asli yang di-input
                $nominalTerbayar = (float) $pembelian->metodePembayarans->sum('amount');

                $totalUangMuka = $nominalTerbayar;
                $sisaHutang    = max(0, $grandTotal - $nominalTerbayar);
            }

            // ─── KREDIT 1: KAS / BANK MENCATAT PENGELUARAN ───
            if ($totalUangMuka > 0) {
                // Tentukan kode akun penampung
                $metodeUtama = ($methodString === PembelianMetodePembayaran::METODE_TRANSFER)
                    ? self::KODE_BANK_INTAN
                    : self::KODE_KAS_DEFAULT;

                $namaKas = $this->getNamaAkun($metodeUtama);

                if (empty($namaKas)) {
                    $namaKas = ($metodeUtama === self::KODE_BANK_INTAN) ? 'bank PT INTAN' : 'kas Tunai Mut';
                }

                $keteranganHeaderKas = "Pembayaran via " . strtoupper($namaKas) . " | Nota: {$nota} | {$supplier}";

                if (!empty($pembayaranDetail?->reference_number)) {
                    $keteranganHeaderKas .= " [Ref: #{$pembayaranDetail->reference_number}]";
                }
                if (!empty($pembayaranDetail?->catatan)) {
                    $keteranganHeaderKas .= " ({$pembayaranDetail->catatan})";
                }

                $headerKas = JurnalPembantuHeader::create([
                    'no_jurnal_pembantu' => JurnalPembantuHeader::lockForUpdate()->max('no_jurnal_pembantu') + 1,
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'bm',
                    'modul_asal'         => 'pembelian_barang',
                    'jurnal'             => $noJurnal,
                    'no_akun'            => $metodeUtama,
                    'nama_akun'          => $namaKas,
                    'map'                => 'k',
                    'keterangan'         => $keteranganHeaderKas,
                    'no_dokumen'         => $nota,
                    'total_nilai'        => $totalUangMuka,
                    'status'             => JurnalPembantuHeader::STATUS_DRAFT,
                    'dibuat_oleh'        => $userId,
                ]);

                // Tentukan keterangan teks label item yang sesuai realita
                $labelKeteranganItem = "Pelunasan Transaksi secara " . strtoupper($methodString);
                if ($methodString === PembelianMetodePembayaran::METODE_LAINNYA) {
                    $labelKeteranganItem = "Pembayaran Uang Muka (Down Payment)";
                }

                JurnalPembantuItem::create([
                    'jurnal_pembantu_header_id' => $headerKas->id,
                    'urut'         => 1,
                    'jenis_pihak'  => 'supplier',
                    'nama_pihak'   => $supplier,
                    'no_dokumen'   => $nota,
                    'nama_barang'  => null,
                    'keterangan'   => $labelKeteranganItem,
                    'banyak'       => 1,
                    'harga'        => $totalUangMuka,
                    'jumlah'       => $totalUangMuka,
                    'status'       => true,
                    'created_by'   => $userId,
                ]);
            }

            // ─── KREDIT 2: TIMBULNYA SISA HUTANG ───
            if ($sisaHutang > 0) {
                $namaHutang = $this->getNamaAkun(self::KODE_HUTANG_DAGANG);
                if (empty($namaHutang)) {
                    $namaHutang = 'Hutang Dagang';
                }

                $headerHutang = JurnalPembantuHeader::create([
                    'no_jurnal_pembantu' => JurnalPembantuHeader::lockForUpdate()->max('no_jurnal_pembantu') + 1,
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'bm',
                    'modul_asal'         => 'pembelian_barang',
                    'jurnal'             => $noJurnal,
                    'no_akun'            => self::KODE_HUTANG_DAGANG,
                    'nama_akun'          => $namaHutang,
                    'map'                => 'k',
                    'keterangan'         => "Timbul Sisa Hutang Nota: {$nota} | {$supplier}",
                    'no_dokumen'         => $nota,
                    'total_nilai'        => $sisaHutang,
                    'status'             => JurnalPembantuHeader::STATUS_DRAFT,
                    'dibuat_oleh'        => $userId,
                ]);

                $urutKredit = 1;
                $totalSub   = $pembelian->detailPembelians->sum('subtotal');
                $sisaBarangHutang = max(0, $totalSub - $totalUangMuka);

                if ($sisaBarangHutang > 0) {
                    $this->buatItemDetail($headerHutang->id, $urutKredit++, $supplier, $nota, "Alokasi Sisa Nilai Pokok Barang", $sisaBarangHutang, $userId);
                }
                if ($ongkir > 0) {
                    $this->buatItemDetail($headerHutang->id, $urutKredit++, $supplier, $nota, "Alokasi Komponen Biaya Ongkos Kirim", $ongkir, $userId);
                }
                if ($biayaLain > 0) {
                    $this->buatItemDetail($headerHutang->id, $urutKredit++, $supplier, $nota, "Alokasi Komponen Biaya Lain-lain", $biayaLain, $userId);
                }
            }
        });
    }

    private function buatItemDetail(int $headerId, int $urut, string $supplier, string $nota, string $ket, float $nominal, int $userId): void
    {
        JurnalPembantuItem::create([
            'jurnal_pembantu_header_id' => $headerId,
            'urut'         => $urut,
            'jenis_pihak'  => 'supplier',
            'nama_pihak'   => $supplier,
            'no_dokumen'   => $nota,
            'keterangan'   => $ket,
            'banyak'       => 1,
            'harga'        => $nominal,
            'jumlah'       => $nominal,
            'status'       => true,
            'created_by'   => $userId,
        ]);
    }

    private function getNamaAkun(string $kode): string
    {
        return SubAnakAkun::where('kode_sub_anak_akun', $kode)->value('nama_sub_anak_akun') ?? '';
    }
}
