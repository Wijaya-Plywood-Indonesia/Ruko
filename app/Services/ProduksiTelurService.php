<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\JurnalPembantuHeader;
use App\Models\JurnalPembantuItem;
use App\Models\JurnalUmum;
use App\Models\ProduksiPakan;
use App\Models\ProduksiPakanCampuran;
use App\Models\ProduksiTelur;
use App\Models\SubAnakAkun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProduksiTelurService
{
    /**
     * Buat jurnal harian (konsolidasi) dari produksi telur
     */
    public function buatJurnalDariProduksi(ProduksiTelur $produksi, int $userId): void
    {
        $tgl = $produksi->tanggal->toDateString();
        $nota = 'PRODTELUR-'.$produksi->id.'-'.$tgl;
        $ket = 'Produksi Telur Harian | Tgl: '.$tgl;

        DB::transaction(function () use ($produksi, $userId, $tgl, $nota, $ket) {
            // 1. Bersihkan jurnal lama jika ada
            $this->hapusJurnalLama($nota);

            // 2. Ambil hasil kandang (Peti, Kiloan, Bentes) — "Sisa" sengaja TIDAK ikut jurnal
            $hasilPeti = (float) ($produksi->hasil_peti ?? 0);
            $hasilKiloan = (float) ($produksi->hasil_kiloan ?? 0);
            $hasilBentes = (float) ($produksi->hasil_bentes ?? 0);

            $petiKeKg = $hasilPeti * ProduksiTelur::PETI_TO_KG;

            // 3. Definisikan 3 komponen hasil kandang yang masuk jurnal (debit)
            $komponenTelur = [
                [
                    'kode_akun' => '1411-00',
                    'label_default' => 'Telur Petian Ruko',
                    'qty' => $petiKeKg,
                    'keterangan' => 'Hasil produksi telur petian Ruko',
                ],
                [
                    'kode_akun' => '1412-00',
                    'label_default' => 'Telur Kiloan Ruko',
                    'qty' => $hasilKiloan,
                    'keterangan' => 'Hasil produksi telur kiloan Ruko',
                ],
                [
                    'kode_akun' => '1413-00',
                    'label_default' => 'Telur Bentes Ruko',
                    'qty' => $hasilBentes,
                    'keterangan' => 'Hasil produksi telur bentes Ruko',
                ],
            ];

            $totalNilaiTelur = 0.0;
            $debitTelur = [];

            foreach ($komponenTelur as $komponen) {
                if ($komponen['qty'] <= 0) {
                    continue;
                }

                $barang = Barang::whereHas('subAnakAkun', function ($q) use ($komponen) {
                    $q->where('kode_sub_anak_akun', $komponen['kode_akun']);
                })->first();

                $subAkun = $barang?->subAnakAkun
                    ?? SubAnakAkun::where('kode_sub_anak_akun', $komponen['kode_akun'])->first();

                $namaAkun = $subAkun ? $subAkun->nama_sub_anak_akun : $komponen['label_default'];
                $namaBarang = $barang ? $barang->nama_barang : $komponen['label_default'];
                $harga = $barang ? (float) $barang->harga_jual : 0;
                $nilai = $komponen['qty'] * $harga;

                $debitTelur[] = [
                    'no_akun' => $komponen['kode_akun'],
                    'nama_akun' => $namaAkun,
                    'nama_barang' => $namaBarang,
                    'keterangan' => $komponen['keterangan'],
                    'banyak' => $komponen['qty'],
                    'harga' => $harga,
                    'total_nilai' => $nilai,
                ];

                $totalNilaiTelur += $nilai;
            }

            if (empty($debitTelur)) {
                Log::info('[ProduksiTelurService] Tidak ada hasil kandang (peti/kiloan/bentes), jurnal tidak dibuat.');

                return;
            }

            // 4. Hitung pemakaian pakan pada tanggal yang sama
            $feedsUsed = [];
            $totalNilaiFeed = 0.0;

            $prodPakan = ProduksiPakan::whereDate('tanggal_produksi', $tgl)->first();
            if ($prodPakan) {
                $campurans = ProduksiPakanCampuran::where('id_produksi_pakan', $prodPakan->id)
                    ->with('barang.subAnakAkun')
                    ->get();

                foreach ($campurans as $c) {
                    $totalQty = (float) ($c->keluar_pullet + $c->keluar_l1 + $c->keluar_l2);
                    if ($totalQty <= 0) {
                        continue;
                    }

                    $barangFeed = $c->barang;
                    if (! $barangFeed) {
                        continue;
                    }

                    $kodeAkunFeed = $barangFeed->subAnakAkun?->kode_sub_anak_akun ?? '1500-00';
                    $namaAkunFeed = $barangFeed->subAnakAkun?->nama_sub_anak_akun ?? $barangFeed->nama_barang;
                    $hargaJualFeed = (float) $barangFeed->harga_jual;
                    $nilaiFeed = $totalQty * $hargaJualFeed;

                    $feedsUsed[] = [
                        'no_akun' => $kodeAkunFeed,
                        'nama_akun' => $namaAkunFeed,
                        'nama_barang' => $barangFeed->nama_barang,
                        'banyak' => $totalQty,
                        'harga' => $hargaJualFeed,
                        'total_nilai' => $nilaiFeed,
                    ];

                    $totalNilaiFeed += $nilaiFeed;
                }
            }

            // 5. Hitung selisih (Pendapatan Kelebihan Produksi Telur)
            // Selisih = Telur (Peti+Kiloan+Bentes) - Pakan
            $selisih = $totalNilaiTelur - $totalNilaiFeed;

            // 6. Generate nomor jurnal grup
            $nextJurnal = (int) (JurnalPembantuHeader::lockForUpdate()->max('jurnal') ?? 0) + 1;
            $maxJU = (int) (JurnalUmum::lockForUpdate()->max('jurnal') ?? 0);
            $nextJurnal = max($nextJurnal, $maxJU) + 1;

            // ── A. DEBET: Akun Telur (Petian / Kiloan / Bentes) ──────────
            $urutDebit = 1;
            foreach ($debitTelur as $telur) {
                $nextNoJP = (int) (JurnalPembantuHeader::lockForUpdate()->max('no_jurnal_pembantu') ?? 0) + 1;
                $hDebit = JurnalPembantuHeader::create([
                    'no_jurnal_pembantu' => $nextNoJP,
                    'tgl_transaksi' => $tgl,
                    'jenis_transaksi' => 'pk',
                    'modul_asal' => 'produksi_telur',
                    'jurnal' => $nextJurnal,
                    'no_akun' => $telur['no_akun'],
                    'nama_akun' => $telur['nama_akun'],
                    'map' => 'd',
                    'keterangan' => $ket,
                    'no_dokumen' => $nota,
                    'status' => JurnalPembantuHeader::STATUS_DRAFT,
                    'dibuat_oleh' => $userId,
                ]);

                JurnalPembantuItem::create([
                    'jurnal_pembantu_header_id' => $hDebit->id,
                    'urut' => $urutDebit++,
                    'nama_barang' => $telur['nama_barang'],
                    'no_dokumen' => $nota,
                    'keterangan' => $telur['keterangan'],
                    'banyak' => $telur['banyak'],
                    'harga' => $telur['harga'],
                    'status' => true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            // ── B. KREDIT: Akun Pakan yang Digunakan ─────────────────────
            $urutKredit = 1;
            foreach ($feedsUsed as $feed) {
                $nextNoJP = (int) (JurnalPembantuHeader::lockForUpdate()->max('no_jurnal_pembantu') ?? 0) + 1;
                $hKredit = JurnalPembantuHeader::create([
                    'no_jurnal_pembantu' => $nextNoJP,
                    'tgl_transaksi' => $tgl,
                    'jenis_transaksi' => 'pk',
                    'modul_asal' => 'produksi_telur',
                    'jurnal' => $nextJurnal,
                    'no_akun' => $feed['no_akun'],
                    'nama_akun' => $feed['nama_akun'],
                    'map' => 'k',
                    'keterangan' => $ket,
                    'no_dokumen' => $nota,
                    'status' => JurnalPembantuHeader::STATUS_DRAFT,
                    'dibuat_oleh' => $userId,
                ]);

                JurnalPembantuItem::create([
                    'jurnal_pembantu_header_id' => $hKredit->id,
                    'urut' => $urutKredit++,
                    'nama_barang' => $feed['nama_barang'],
                    'no_dokumen' => $nota,
                    'keterangan' => 'Pemakaian pakan untuk produksi telur',
                    'banyak' => $feed['banyak'],
                    'harga' => $feed['harga'],
                    'status' => true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            // ── C. KREDIT: Pendapatan Kelebihan Produksi Telur (Selisih) ──
            $selisihSubAkun = SubAnakAkun::where('kode_sub_anak_akun', '4400-00')->first();
            $namaSelisihAkun = $selisihSubAkun ? $selisihSubAkun->nama_sub_anak_akun : 'Pendapatan Kelebihan Produksi Telur';

            $nextNoJP = (int) (JurnalPembantuHeader::lockForUpdate()->max('no_jurnal_pembantu') ?? 0) + 1;
            $hSelisih = JurnalPembantuHeader::create([
                'no_jurnal_pembantu' => $nextNoJP,
                'tgl_transaksi' => $tgl,
                'jenis_transaksi' => 'pk',
                'modul_asal' => 'produksi_telur',
                'jurnal' => $nextJurnal,
                'no_akun' => '4400-00',
                'nama_akun' => $namaSelisihAkun,
                'map' => 'k',
                'keterangan' => $ket,
                'no_dokumen' => $nota,
                'status' => JurnalPembantuHeader::STATUS_DRAFT,
                'dibuat_oleh' => $userId,
            ]);

            JurnalPembantuItem::create([
                'jurnal_pembantu_header_id' => $hSelisih->id,
                'urut' => 1,
                'nama_barang' => $namaSelisihAkun,
                'no_dokumen' => $nota,
                'keterangan' => 'Selisih produksi telur vs pakan',
                'banyak' => 1,
                'harga' => $selisih,
                'status' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            Log::info("[ProduksiTelurService] Jurnal sukses dibuat untuk nota {$nota}. Total Telur: {$totalNilaiTelur}, Total Pakan: {$totalNilaiFeed}, Selisih: {$selisih}");
        });
    }

    /**
     * Hapus jurnal lama (draft) untuk re-validation
     */
    public function hapusJurnalLama(string $nota): void
    {
        $anyPosted = JurnalPembantuHeader::where('modul_asal', 'produksi_telur')
            ->where('no_dokumen', $nota)
            ->where('status', JurnalPembantuHeader::STATUS_DIPOSTING)
            ->exists();

        if ($anyPosted) {
            throw new \Exception('Jurnal untuk produksi tanggal ini sudah diposting ke Buku Besar. Batalkan posting terlebih dahulu.');
        }

        $headers = JurnalPembantuHeader::where('modul_asal', 'produksi_telur')
            ->where('no_dokumen', $nota)
            ->get();

        foreach ($headers as $header) {
            $header->items()->delete();
            $header->delete();
        }
    }
}
