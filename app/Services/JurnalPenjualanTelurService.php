<?php

namespace App\Services;

use App\Models\AnakAkun;
use App\Models\Barang;
use App\Models\IndukAkun;
use App\Models\JurnalPembantuHeader;
use App\Models\JurnalPembantuItem;
use App\Models\Penjualan;
use App\Models\SubAnakAkun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service Jurnal Pembantu — Penjualan (v7)
 * Fix: HPP tidak dobel, Petian terhitung, dan Jurnal Peti (Peti Kosong vs Peti Isi) dinamis dari DB
 */
class JurnalPenjualanTelurService
{
    const KODE_KAS           = '1121-00';
    const KG_PER_PETI        = 10;
    const HARGA_PETI_DEFAULT = 6000;

    private array $akunCache = [];

    public function buatJurnalDariPenjualan(Penjualan $penjualan, int $userId): void
    {
        $penjualan->loadMissing([
            'details.barang.subAnakAkun',
            'details.barang.akunPendapatan',
            'details.barang.akunHpp',
            'rekeningPerusahaan.subAnakAkun',
        ]);

        $itemTelur = collect();
        $itemLain  = collect();

        foreach ($penjualan->details as $detail) {
            if (!$detail->barang) continue;

            $nama = strtolower($detail->nama_barang ?? '');

            if ($this->isTelur($nama)) {
                $itemTelur->push($detail);
            } else {
                $itemLain->push($detail);
            }
        }

        if ($itemTelur->isEmpty() && $itemLain->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($penjualan, $itemTelur, $itemLain, $userId) {

            $tgl      = $penjualan->tanggal->toDateString();
            $nota     = $penjualan->no_nota;
            $customer = $penjualan->nama_customer ?: 'Pelanggan';
            $noJurnal = $this->nextNomorJurnal();

            // ════════════════════════════════════════════════════════
            // BAGIAN TELUR
            // ════════════════════════════════════════════════════════
            if ($itemTelur->isNotEmpty()) {

                $totalTelur  = $itemTelur->sum('subtotal');
                $totalHpp    = $itemTelur->sum(
                    fn($d) => (float) $d->qty * (float) ($d->barang->harga_beli ?? 0)
                );
                
                // 1. Hitung peti dari telur kiloan
                $totalKiloan = $itemTelur
                    ->filter(fn($d) => $this->isKiloan(strtolower($d->nama_barang ?? '')))
                    ->sum('qty');
                $petiDariKiloan = ($totalKiloan > 0 && $totalKiloan % self::KG_PER_PETI === 0)
                    ? (int) ($totalKiloan / self::KG_PER_PETI) : 0;

                // 2. Hitung peti dari telur petian
                $petiDariPetian = $itemTelur
                    ->filter(fn($d) => str_contains(strtolower($d->nama_barang ?? ''), 'petian'))
                    ->sum('qty');

                // 3. Gabungkan total peti
                $jumlahPeti = $petiDariKiloan + $petiDariPetian;

                $ketJual  = $this->ket('Penjualan', $nota, $customer);
                $barisKas = $this->resolveBarisKas($penjualan, $totalTelur);

                // Di bagian D: Kas — Bagian Telur
                foreach ($barisKas as $kas) {
                    $hKas = $this->buatHeader([
                        'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                        'tgl_transaksi'      => $tgl,
                        'jenis_transaksi'    => 'bk',
                        'modul_asal'         => 'penjualan_telur',
                        'jurnal'             => $noJurnal,
                        'no_akun'            => $kas['kode'],
                        'nama_akun'          => $kas['nama'],
                        'map'                => 'd',
                        'keterangan'         => $ketJual,
                        'no_dokumen'         => $nota,
                        'dibuat_oleh'        => $userId,
                    ]);

                    $urut           = 1;
                    $list           = $itemTelur->values();
                    $lastIndex      = $list->count() - 1;
                    $sudahTersimpan = 0.0; // akumulasi jumlah (banyak × harga) yang sudah disimpan

                    foreach ($list as $i => $d) {
                        if ($i === $lastIndex) {
                            $harga = $d->qty > 0
                                ? round(($kas['nominal'] - $sudahTersimpan) / (float) $d->qty, 4)
                                : 0;
                        } else {
                            $harga           = round((float) $d->harga_jual * $kas['proporsi'], 4);
                            $sudahTersimpan += round((float) $d->qty * $harga, 4);
                        }

                        $this->buatItem($hKas->id, [
                            'urut'         => $urut++,
                            'jenis_pihak'  => 'pelanggan',
                            'nama_pihak'   => $customer,
                            'nama_barang'  => $d->nama_barang,
                            'no_dokumen'   => $nota,
                            'no_referensi' => (string) $d->id,
                            'keterangan'   => $d->nama_barang . ' ' . $d->qty . ' ' . ($d->satuan ?? ''),
                            'banyak'       => $d->qty,
                            'harga'        => $harga, // ← harga yang sudah dikoreksi
                            'created_by'   => $userId,
                            'updated_by'   => $userId,
                        ]);
                    }
                }

                // ── K: Pendapatan per akun pendapatan ────────────────────────
                $perPend = $itemTelur->groupBy(
                    fn($d) => $this->kodePerJenis($d->barang)[0]
                );

                foreach ($perPend as $kodePend => $details) {
                    $akunPend = $this->resolveAkun($kodePend);
                    $hPend    = $this->buatHeader([
                        'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                        'tgl_transaksi'      => $tgl,
                        'jenis_transaksi'    => 'bk',
                        'modul_asal'         => 'penjualan_telur',
                        'jurnal'             => $noJurnal,
                        'no_akun'            => $akunPend['kode'],
                        'nama_akun'          => $akunPend['nama'],
                        'map'                => 'k',
                        'keterangan'         => $ketJual,
                        'no_dokumen'         => $nota,
                        'dibuat_oleh'        => $userId,
                    ]);
                    $urut = 1;
                    foreach ($details as $d) {
                        $this->buatItem($hPend->id, [
                            'urut'         => $urut++,
                            'jenis_pihak'  => 'pelanggan',
                            'nama_pihak'   => $customer,
                            'nama_barang'  => $d->nama_barang,
                            'no_dokumen'   => $nota,
                            'no_referensi' => (string) $d->id,
                            'keterangan'   => $d->nama_barang . ' ' . $d->qty . ' ' . ($d->satuan ?? ''),
                            'banyak'       => $d->qty,
                            'harga'        => $d->harga_jual,
                            'created_by'   => $userId,
                            'updated_by'   => $userId,
                        ]);
                    }
                }

                // ── D: HPP (group per kode_hpp) & K: Persediaan (group per kode_pers) ──
                if ($totalHpp > 0) {
                    $ketHpp = $this->ket('HPP Penjualan', $nota);

                    // [FIX] Group HPP hanya berdasar kode_hpp → tidak dobel
                    $perHpp = $itemTelur->groupBy(
                        fn($d) => $this->kodePerJenis($d->barang)[1]
                    );

                    foreach ($perHpp as $kodeHpp => $detailsHpp) {
                        $adaHpp = $detailsHpp->filter(
                            fn($d) => (float) ($d->barang->harga_beli ?? 0) > 0
                        );
                        if ($adaHpp->isEmpty()) continue;

                        // 1 header HPP per kode akun HPP
                        $akunHpp = $this->resolveAkun($kodeHpp);
                        $hHpp    = $this->buatHeader([
                            'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                            'tgl_transaksi'      => $tgl,
                            'jenis_transaksi'    => 'bk',
                            'modul_asal'         => 'penjualan_telur',
                            'jurnal'             => $noJurnal,
                            'no_akun'            => $akunHpp['kode'],
                            'nama_akun'          => $akunHpp['nama'],
                            'map'                => 'd',
                            'keterangan'         => $ketHpp,
                            'no_dokumen'         => $nota,
                            'dibuat_oleh'        => $userId,
                        ]);
                        $urut = 1;
                        foreach ($adaHpp as $d) {
                            $this->buatItem($hHpp->id, [
                                'urut'         => $urut++,
                                'nama_barang'  => $d->nama_barang,
                                'no_dokumen'   => $nota,
                                'no_referensi' => (string) $d->id,
                                'keterangan'   => 'HPP ' . $d->nama_barang,
                                'banyak'       => $d->qty,
                                'harga'        => $d->barang->harga_beli,
                                'created_by'   => $userId,
                                'updated_by'   => $userId,
                            ]);
                        }

                        // K: Persediaan → group per kode_persediaan di dalam loop HPP
                        $perPers = $adaHpp->groupBy(
                            fn($d) => $this->kodePerJenis($d->barang)[2]
                        );

                        foreach ($perPers as $kodePers => $detailsPers) {
                            $akunPers = $this->resolveAkun($kodePers);
                            $hPers    = $this->buatHeader([
                                'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                                'tgl_transaksi'      => $tgl,
                                'jenis_transaksi'    => 'bk',
                                'modul_asal'         => 'penjualan_telur',
                                'jurnal'             => $noJurnal,
                                'no_akun'            => $akunPers['kode'],
                                'nama_akun'          => $akunPers['nama'],
                                'map'                => 'k',
                                'keterangan'         => $ketHpp,
                                'no_dokumen'         => $nota,
                                'dibuat_oleh'        => $userId,
                            ]);
                            $urut = 1;
                            foreach ($detailsPers as $d) {
                                $this->buatItem($hPers->id, [
                                    'urut'         => $urut++,
                                    'nama_barang'  => $d->nama_barang,
                                    'no_dokumen'   => $nota,
                                    'no_referensi' => (string) $d->id,
                                    'keterangan'   => 'Keluar stok ' . $d->nama_barang,
                                    'banyak'       => $d->qty,
                                    'harga'        => $d->barang->harga_beli,
                                    'created_by'   => $userId,
                                    'updated_by'   => $userId,
                                ]);
                            }
                        }
                    }
                }

                // ── Peti otomatis (Peti Isi Telur KREDIT, Peti Kosong DEBIT) ─────────
                if ($jumlahPeti > 0) {
                    $ketPeti = $this->ket('Konversi Peti Telur', $nota, $customer);

                    // Ambil barang dari DB beserta relasi akun persediaannya
                    $brgPetiKosong = Barang::with('subAnakAkun')->where('nama_barang', 'like', '%peti%kosong%')->first();
                    $brgPetiIsi    = Barang::with('subAnakAkun')->where('nama_barang', 'like', '%peti%isi%telur%')->first();

                    // Resolve Kode Akun & HPP dinamis, fallback jika gagal query
                    $kodePetiKosong  = $brgPetiKosong?->subAnakAkun?->kode_sub_anak_akun ?? '1600-01';
                    $hargaPetiKosong = (float) ($brgPetiKosong?->harga_beli ?? self::HARGA_PETI_DEFAULT);

                    $kodePetiIsi  = $brgPetiIsi?->subAnakAkun?->kode_sub_anak_akun ?? '1600-02';
                    $hargaPetiIsi = (float) ($brgPetiIsi?->harga_beli ?? self::HARGA_PETI_DEFAULT);

                    // D: Peti Kosong Bertambah
                    $akunPetiKosong = $this->resolveAkun($kodePetiKosong);
                    $hPetiKosong = $this->buatHeader([
                        'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                        'tgl_transaksi'      => $tgl,
                        'jenis_transaksi'    => 'bk',
                        'modul_asal'         => 'penjualan_telur',
                        'jurnal'             => $noJurnal,
                        'no_akun'            => $akunPetiKosong['kode'],
                        'nama_akun'          => $akunPetiKosong['nama'],
                        'map'                => 'd',
                        'keterangan'         => $ketPeti,
                        'no_dokumen'         => $nota,
                        'dibuat_oleh'        => $userId,
                    ]);
                    $this->buatItem($hPetiKosong->id, [
                        'urut'        => 1,
                        'nama_barang' => $brgPetiKosong?->nama_barang ?? 'Peti Kosong',
                        'no_dokumen'  => $nota,
                        'keterangan'  => 'Masuk stok peti kosong ' . $jumlahPeti . ' pcs (Kiloan: ' . $petiDariKiloan . ', Petian: ' . $petiDariPetian . ')',
                        'banyak'      => $jumlahPeti,
                        'harga'       => $hargaPetiKosong,
                        'created_by'  => $userId,
                        'updated_by'  => $userId,
                    ]);

                    // K: Peti Isi Telur Berkurang
                    $akunPetiIsi = $this->resolveAkun($kodePetiIsi);
                    $hPetiIsi = $this->buatHeader([
                        'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                        'tgl_transaksi'      => $tgl,
                        'jenis_transaksi'    => 'bk',
                        'modul_asal'         => 'penjualan_telur',
                        'jurnal'             => $noJurnal,
                        'no_akun'            => $akunPetiIsi['kode'],
                        'nama_akun'          => $akunPetiIsi['nama'],
                        'map'                => 'k',
                        'keterangan'         => $ketPeti,
                        'no_dokumen'         => $nota,
                        'dibuat_oleh'        => $userId,
                    ]);
                    $this->buatItem($hPetiIsi->id, [
                        'urut'        => 1,
                        'nama_barang' => $brgPetiIsi?->nama_barang ?? 'Peti Isi Telur',
                        'no_dokumen'  => $nota,
                        'keterangan'  => 'Keluar stok peti isi telur ' . $jumlahPeti . ' pcs (Kiloan: ' . $petiDariKiloan . ', Petian: ' . $petiDariPetian . ')',
                        'banyak'      => $jumlahPeti,
                        'harga'       => $hargaPetiIsi,
                        'created_by'  => $userId,
                        'updated_by'  => $userId,
                    ]);
                }
            }

            // ════════════════════════════════════════════════════════
            // BAGIAN BARANG LAIN (Ayam, Pakan, Kayu, dll)
            // ════════════════════════════════════════════════════════
            if ($itemLain->isNotEmpty()) {

                $totalLain = $itemLain->sum('subtotal');
                $barisKas  = $this->resolveBarisKas($penjualan, $totalLain);

                // Group per akun pendapatan
                $perJenisLain = $itemLain->groupBy(
                    fn($d) => $this->kodePerJenis($d->barang)[0]
                );

                foreach ($perJenisLain as $kodePend => $details) {
                    $namaBarangPertama = $details->first()->nama_barang ?? 'Barang';
                    $ketLain           = $this->ket('Penjualan ' . $namaBarangPertama, $nota, $customer);
                    $akunPend          = $this->resolveAkun($kodePend);

                    foreach ($barisKas as $kas) {
                        $hKas = $this->buatHeader([
                            'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                            'tgl_transaksi'      => $tgl,
                            'jenis_transaksi'    => 'bk',
                            'modul_asal'         => 'penjualan_telur',
                            'jurnal'             => $noJurnal,
                            'no_akun'            => $kas['kode'],
                            'nama_akun'          => $kas['nama'],
                            'map'                => 'd',
                            'keterangan'         => $ketLain,
                            'no_dokumen'         => $nota,
                            'dibuat_oleh'        => $userId,
                        ]);

                        $urut           = 1;
                        $list           = $details->values();
                        $lastIndex      = $list->count() - 1;
                        $sudahTersimpan = 0.0;

                        foreach ($list as $i => $d) {
                            if ($i === $lastIndex) {
                                $harga = $d->qty > 0
                                    ? round(($kas['nominal'] - $sudahTersimpan) / (float) $d->qty, 4)
                                    : 0;
                            } else {
                                $harga           = round((float) $d->harga_jual * $kas['proporsi'], 4);
                                $sudahTersimpan += round((float) $d->qty * $harga, 4);
                            }

                            $this->buatItem($hKas->id, [
                                'urut'         => $urut++,
                                'jenis_pihak'  => 'pelanggan',
                                'nama_pihak'   => $customer,
                                'nama_barang'  => $d->nama_barang,
                                'no_dokumen'   => $nota,
                                'no_referensi' => (string) $d->id,
                                'keterangan'   => $d->nama_barang . ' ' . $d->qty . ' ' . ($d->satuan ?? ''),
                                'banyak'       => $d->qty,
                                'harga'        => $harga,
                                'created_by'   => $userId,
                                'updated_by'   => $userId,
                            ]);
                        }
                    }

                    // K: Pendapatan
                    $hPend = $this->buatHeader([
                        'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                        'tgl_transaksi'      => $tgl,
                        'jenis_transaksi'    => 'bk',
                        'modul_asal'         => 'penjualan_telur',
                        'jurnal'             => $noJurnal,
                        'no_akun'            => $akunPend['kode'],
                        'nama_akun'          => $akunPend['nama'],
                        'map'                => 'k',
                        'keterangan'         => $ketLain,
                        'no_dokumen'         => $nota,
                        'dibuat_oleh'        => $userId,
                    ]);
                    $urut = 1;
                    foreach ($details as $d) {
                        $this->buatItem($hPend->id, [
                            'urut'         => $urut++,
                            'jenis_pihak'  => 'pelanggan',
                            'nama_pihak'   => $customer,
                            'nama_barang'  => $d->nama_barang,
                            'no_dokumen'   => $nota,
                            'no_referensi' => (string) $d->id,
                            'keterangan'   => $d->nama_barang . ' ' . $d->qty . ' ' . ($d->satuan ?? ''),
                            'banyak'       => $d->qty,
                            'harga'        => $d->harga_jual,
                            'created_by'   => $userId,
                            'updated_by'   => $userId,
                        ]);
                    }

                    // D: HPP & K: Persediaan (jika ada harga_beli)
                    $adaHpp = $details->filter(
                        fn($d) => (float) ($d->barang->harga_beli ?? 0) > 0
                    );

                    if ($adaHpp->isNotEmpty()) {
                        $ketHpp = $this->ket('HPP ' . $namaBarangPertama, $nota);

                        // [FIX] Group HPP per kode_hpp → tidak dobel
                        $perHppLain = $adaHpp->groupBy(
                            fn($d) => $this->kodePerJenis($d->barang)[1]
                        );

                        foreach ($perHppLain as $kodeHpp => $detailsHpp) {
                            $akunHpp = $this->resolveAkun($kodeHpp);
                            $hHpp    = $this->buatHeader([
                                'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                                'tgl_transaksi'      => $tgl,
                                'jenis_transaksi'    => 'bk',
                                'modul_asal'         => 'penjualan_telur',
                                'jurnal'             => $noJurnal,
                                'no_akun'            => $akunHpp['kode'],
                                'nama_akun'          => $akunHpp['nama'],
                                'map'                => 'd',
                                'keterangan'         => $ketHpp,
                                'no_dokumen'         => $nota,
                                'dibuat_oleh'        => $userId,
                            ]);
                            $urut = 1;
                            foreach ($detailsHpp as $d) {
                                $this->buatItem($hHpp->id, [
                                    'urut'         => $urut++,
                                    'nama_barang'  => $d->nama_barang,
                                    'no_dokumen'   => $nota,
                                    'no_referensi' => (string) $d->id,
                                    'keterangan'   => 'HPP ' . $d->nama_barang,
                                    'banyak'       => $d->qty,
                                    'harga'        => $d->barang->harga_beli,
                                    'created_by'   => $userId,
                                    'updated_by'   => $userId,
                                ]);
                            }

                            // K: Persediaan → group per kode_persediaan
                            $perPersLain = $detailsHpp->groupBy(
                                fn($d) => $this->kodePerJenis($d->barang)[2]
                            );

                            foreach ($perPersLain as $kodePers => $detailsPers) {
                                $akunPers = $this->resolveAkun($kodePers);
                                $hPers    = $this->buatHeader([
                                    'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                                    'tgl_transaksi'      => $tgl,
                                    'jenis_transaksi'    => 'bk',
                                    'modul_asal'         => 'penjualan_telur',
                                    'jurnal'             => $noJurnal,
                                    'no_akun'            => $akunPers['kode'],
                                    'nama_akun'          => $akunPers['nama'],
                                    'map'                => 'k',
                                    'keterangan'         => $ketHpp,
                                    'no_dokumen'         => $nota,
                                    'dibuat_oleh'        => $userId,
                                ]);
                                $urut = 1;
                                foreach ($detailsPers as $d) {
                                    $this->buatItem($hPers->id, [
                                        'urut'         => $urut++,
                                        'nama_barang'  => $d->nama_barang,
                                        'no_dokumen'   => $nota,
                                        'no_referensi' => (string) $d->id,
                                        'keterangan'   => 'Keluar stok ' . $d->nama_barang,
                                        'banyak'       => $d->qty,
                                        'harga'        => $d->barang->harga_beli,
                                        'created_by'   => $userId,
                                        'updated_by'   => $userId,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    // ══════════════════════════════════════════════════════════════
    // RESOLVE KAS
    // ══════════════════════════════════════════════════════════════

    private function resolveBarisKas(Penjualan $penjualan, float $totalNilai): array
    {
        $bayarTunai    = (float) ($penjualan->bayar_tunai    ?? 0);
        $bayarTransfer = (float) ($penjualan->bayar_transfer ?? 0);
        $total         = $bayarTunai + $bayarTransfer;

        if ($total <= 0) {
            $metode        = strtolower($penjualan->metode_pembayaran ?? 'tunai');
            $bayarTunai    = $metode !== 'transfer' ? $totalNilai : 0;
            $bayarTransfer = $metode === 'transfer'  ? $totalNilai : 0;
            $total         = $totalNilai;
        }

        $baris = [];

        if ($bayarTunai > 0) {
            $akun    = $this->resolveAkun(self::KODE_KAS);
            $baris[] = [
                'kode'     => $akun['kode'],
                'nama'     => $akun['nama'],
                'proporsi' => $bayarTunai / $total,
                'nominal'  => $bayarTunai,
            ];
        }

        if ($bayarTransfer > 0) {
            $kodeBank = $penjualan->rekeningPerusahaan?->subAnakAkun?->kode_sub_anak_akun;

            if (!$kodeBank) {
                Log::warning("[JurnalPenjualan] Rekening transfer {$penjualan->no_rekening} belum di-mapping. Fallback ke kas tunai. Nota: {$penjualan->no_nota}.");
                $kodeBank = self::KODE_KAS;
            }

            $akun    = $this->resolveAkun($kodeBank);
            $baris[] = [
                'kode'     => $akun['kode'],
                'nama'     => $akun['nama'],
                'proporsi' => $bayarTransfer / $total,
                'nominal'  => $bayarTransfer,
            ];
        }

        return $baris;
    }

    // ══════════════════════════════════════════════════════════════
    // RESOLVER AKUN
    // ══════════════════════════════════════════════════════════════

    private function resolveAkun(string $kode): array
    {
        if (isset($this->akunCache[$kode])) {
            return $this->akunCache[$kode];
        }

        $sub = SubAnakAkun::where('kode_sub_anak_akun', $kode)->where('status', 'aktif')->first();
        if ($sub) {
            return $this->akunCache[$kode] = [
                'kode' => $sub->kode_sub_anak_akun,
                'nama' => $sub->nama_sub_anak_akun,
            ];
        }

        $anak = AnakAkun::where('kode_anak_akun', $kode)->where('status', 'aktif')->first();
        if ($anak) {
            return $this->akunCache[$kode] = [
                'kode' => $anak->kode_anak_akun,
                'nama' => $anak->nama_anak_akun,
            ];
        }

        $induk = IndukAkun::where('kode_induk_akun', $kode)->where('status', 'aktif')->first();
        if ($induk) {
            return $this->akunCache[$kode] = [
                'kode' => $induk->kode_induk_akun,
                'nama' => $induk->nama_induk_akun,
            ];
        }

        Log::warning("[JurnalPenjualan] Kode akun '{$kode}' tidak ditemukan di master akun.");

        return $this->akunCache[$kode] = [
            'kode' => $kode,
            'nama' => '⚠ Akun tidak ditemukan: ' . $kode,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // HELPER
    // ══════════════════════════════════════════════════════════════

    private function isTelur(string $namaLower): bool
    {
        return str_contains($namaLower, 'telur')
            || str_contains($namaLower, '_butir')
            || str_contains($namaLower, '_kilo')
            || str_contains($namaLower, '_kg')
            || str_contains($namaLower, 'petian')
            || str_contains($namaLower, 'bentes');
    }

    private function isKiloan(string $namaLower): bool
    {
        if (str_contains($namaLower, 'bentes')) return false;
        if (str_contains($namaLower, 'petian') || str_contains($namaLower, '_butir')) return false;

        return str_contains($namaLower, 'kilo')
            || str_contains($namaLower, '_kg')
            || str_contains($namaLower, 'telur ruko')
            || str_contains($namaLower, 'telur_ruko');
    }

    private function kodePerJenis(?Barang $barang = null): array
    {
        $kodePend = $barang?->akunPendapatan?->kode_sub_anak_akun;
        $kodeHpp  = $barang?->akunHpp?->kode_sub_anak_akun;
        $kodePers = $barang?->subAnakAkun?->kode_sub_anak_akun;

        if (!$kodePend) $kodePend = '4100-01';
        if (!$kodeHpp)  $kodeHpp  = '6000-01';
        if (!$kodePers) $kodePers = '1411-00';

        return [$kodePend, $kodeHpp, $kodePers];
    }

    private function ket(string $prefix, string $nota, ?string $customer = null): string
    {
        $k = $prefix . ' | No.Nota: ' . $nota;
        if ($customer) $k .= ' | ' . $customer;
        return $k;
    }

    private function buatHeader(array $data): JurnalPembantuHeader
    {
        return JurnalPembantuHeader::create(array_merge([
            'status'              => JurnalPembantuHeader::STATUS_DRAFT,
            'adalah_jurnal_balik' => false,
            'total_nilai'         => 0,
        ], $data));
    }

    private function buatItem(int $headerId, array $data): JurnalPembantuItem
    {
        return JurnalPembantuItem::create(array_merge([
            'jurnal_pembantu_header_id' => $headerId,
            'status'                    => true,
            'jumlah'                    => 0,
        ], $data));
    }

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
