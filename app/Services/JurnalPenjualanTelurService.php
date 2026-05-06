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
 * Service Jurnal Pembantu — Penjualan Telur  (v5)
 *
 * PERUBAHAN dari v4:
 * - Akun kas tidak lagi hardcode ke 1121-00
 * - Jika metode_pembayaran = 'tunai'   → tetap 1121-00 (KODE_KAS)
 * - Jika metode_pembayaran = 'transfer' → ambil dari RekeningPerusahaan.subAnakAkun
 *   (via FK sub_anak_akun_id yang sudah di-mapping di Master Data)
 *
 * STRUKTUR JURNAL (1 nota = 1 no_jurnal):
 * ───────────────────────────────────────────────────────────────────────
 *  D  1121-00   Kas Tunai Mut              ← tunai
 *  D  1212-00   Bank PT INTAN              ← transfer (sesuai rekening tujuan)
 *  K  4100-01   Penjualan Telur Petian
 *  K  4100-02   Penjualan Telur Kiloan
 *  K  4100-03   Penjualan Telur Bentes
 *  D  6000-01   HPP Telor
 *  D  6000-02   HPP Bentes
 *  K  1411-00   Persediaan Telur Petian
 *  K  1412-00   Persediaan Telur Kiloan
 *  K  1413-00   Persediaan Telur Bentes
 *
 *  PETI OTOMATIS (jurnal yang SAMA):
 *  Syarat: total qty kiloan adalah kelipatan 10
 *    D  1122-00   Kas Tunai Penjualan Lain
 *    K  1600-01   Peti Kosong
 */
class JurnalPenjualanTelurService
{
    // ══════════════════════════════════════════════════════════════
    // KODE AKUN — sesuai kode_sub_anak_akun di DB
    // ══════════════════════════════════════════════════════════════

    /** Kas tunai default (jika metode_pembayaran = tunai) */
    const KODE_KAS          = '1121-00';

    /** Kas peti (selalu tunai, tidak terpengaruh metode bayar) */
    const KODE_KAS_PETI     = '1122-00';

    /** Peti kosong (persediaan) */
    const KODE_PETI         = '1600-01';

    /** 1 peti = berapa kg kiloan */
    const KG_PER_PETI       = 10;

    /** Harga peti fallback jika tidak ada di master barang */
    const HARGA_PETI_DEFAULT = 6000;

    /**
     * Mapping nama_barang → ['kode_pendapatan', 'kode_hpp', 'kode_persediaan']
     */
    const KODE_PER_JENIS = [
        'bentes'      => ['4100-03', '6000-02', '1413-00'],
        'telur_kilo'  => ['4100-02', '6000-01', '1412-00'],
        'telur kilo'  => ['4100-02', '6000-01', '1412-00'],
        'telur ruko'  => ['4100-02', '6000-01', '1412-00'],
        'telur_ruko'  => ['4100-02', '6000-01', '1412-00'],
        '_kg'         => ['4100-02', '6000-01', '1412-00'],
        '_kilo'       => ['4100-02', '6000-01', '1412-00'],
        'telur_butir' => ['4100-01', '6000-01', '1411-00'],
        'telur butir' => ['4100-01', '6000-01', '1411-00'],
        '_butir'      => ['4100-01', '6000-01', '1411-00'],
        'telur'       => ['4100-01', '6000-01', '1411-00'],
    ];

    private array $akunCache = [];

    // ══════════════════════════════════════════════════════════════
    // ENTRY POINT
    // ══════════════════════════════════════════════════════════════

    public function buatJurnalDariPenjualan(Penjualan $penjualan, int $userId): void
    {
        // Load relasi yang dibutuhkan termasuk rekening → subAnakAkun
        $penjualan->loadMissing([
            'details.barang',
            'rekeningPerusahaan.subAnakAkun',
        ]);

        $itemTelur = collect();
        foreach ($penjualan->details as $detail) {
            $nama = strtolower($detail->nama_barang ?? '');
            if ($this->isTelur($nama)) {
                $itemTelur->push($detail);
            }
        }

        if ($itemTelur->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($penjualan, $itemTelur, $userId) {

            $tgl      = $penjualan->tanggal->toDateString();
            $nota     = $penjualan->no_nota;
            $customer = $penjualan->nama_customer ?: 'Pelanggan';

            $totalTelur = $itemTelur->sum('subtotal');
            $totalHpp   = $itemTelur->sum(
                fn($d) => (float) $d->qty * (float) ($d->barang->harga_beli ?? 0)
            );

            $totalKiloan = $itemTelur
                ->filter(fn($d) => $this->isKiloan(strtolower($d->nama_barang ?? '')))
                ->sum('qty');

            $jumlahPeti = ($totalKiloan > 0 && $totalKiloan % self::KG_PER_PETI === 0)
                ? (int) ($totalKiloan / self::KG_PER_PETI)
                : 0;

            $noJurnal = $this->nextNomorJurnal();

            // ── Resolve baris kas (tunai, transfer, atau split) ───────────────
            $barisKas = $this->resolveBarisKas($penjualan, $totalTelur);

            // ─── D : Kas (satu atau dua baris jika split) ────────────────────
            if ($totalTelur > 0) {
                $ketJual = $this->ket('Penjualan Telur', $nota, $customer);

                foreach ($barisKas as $kas) {
                    // Nominal per baris kas = proporsional dari total telur
                    $nominalKas = round($kas['nominal'], 2);

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

                    // Items: proporsional sesuai bayar_tunai / bayar_transfer
                    $urut = 1;
                    foreach ($itemTelur as $d) {
                        $this->buatItem($hKas->id, [
                            'urut'         => $urut++,
                            'jenis_pihak'  => 'pelanggan',
                            'nama_pihak'   => $customer,
                            'nama_barang'  => $d->nama_barang,
                            'no_dokumen'   => $nota,
                            'no_referensi' => (string) $d->id,
                            'keterangan'   => $d->nama_barang . ' ' . $d->qty . ' ' . ($d->satuan ?? ''),
                            'banyak'       => $d->qty,
                            // harga proporsional sesuai porsi pembayaran ini
                            'harga'        => round($d->harga_jual * $kas['proporsi'], 2),
                            'created_by'   => $userId,
                            'updated_by'   => $userId,
                        ]);
                    }
                }

                // ─── K : Pendapatan per jenis telur ──────────────────────────
                $perPend = $itemTelur->groupBy(
                    fn($d) => $this->kodePerJenis(strtolower($d->nama_barang ?? ''))[0]
                );

                foreach ($perPend as $kodePend => $details) {
                    $akunPend = $this->resolveAkun($kodePend);

                    $hPend = $this->buatHeader([
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
            }

            // ─── D : HPP  &  K : Persediaan ──────────────────────────────────
            if ($totalHpp > 0) {
                $ketHpp = $this->ket('HPP Penjualan Telur', $nota);

                $perJenis = $itemTelur->groupBy(function ($d) {
                    $kode = $this->kodePerJenis(strtolower($d->nama_barang ?? ''));
                    return $kode[1] . '|' . $kode[2];
                });

                foreach ($perJenis as $pasangan => $details) {
                    [$kodeHpp, $kodePers] = explode('|', $pasangan);

                    $adaHpp = $details->filter(
                        fn($d) => (float) ($d->barang->harga_beli ?? 0) > 0
                    );
                    if ($adaHpp->isEmpty()) continue;

                    // D: HPP
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

                    // K: Persediaan
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
                    foreach ($adaHpp as $d) {
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

            // ─── PETI OTOMATIS ────────────────────────────────────────────────
            if ($jumlahPeti > 0) {
                $hargaPeti = $this->hargaPetiDariDB();
                $ketPeti   = $this->ket('Jual Peti', $nota, $customer);

                // D: Kas Penjualan Lain (1122-00) — peti selalu tunai
                $akunKasPeti = $this->resolveAkun(self::KODE_KAS_PETI);
                $hKasPeti    = $this->buatHeader([
                    'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'bk',
                    'modul_asal'         => 'penjualan_telur',
                    'jurnal'             => $noJurnal,
                    'no_akun'            => $akunKasPeti['kode'],
                    'nama_akun'          => $akunKasPeti['nama'],
                    'map'                => 'd',
                    'keterangan'         => $ketPeti,
                    'no_dokumen'         => $nota,
                    'dibuat_oleh'        => $userId,
                ]);

                $this->buatItem($hKasPeti->id, [
                    'urut'        => 1,
                    'jenis_pihak' => 'pelanggan',
                    'nama_pihak'  => $customer,
                    'nama_barang' => 'Peti Kosong',
                    'no_dokumen'  => $nota,
                    'keterangan'  => 'Jual Peti ' . $jumlahPeti . ' pcs (dari ' . $totalKiloan . ' kg kiloan)',
                    'banyak'      => $jumlahPeti,
                    'harga'       => $hargaPeti,
                    'created_by'  => $userId,
                    'updated_by'  => $userId,
                ]);

                // K: Peti Kosong keluar (1600-01)
                $akunPeti = $this->resolveAkun(self::KODE_PETI);
                $hPetiK   = $this->buatHeader([
                    'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'bk',
                    'modul_asal'         => 'penjualan_telur',
                    'jurnal'             => $noJurnal,
                    'no_akun'            => $akunPeti['kode'],
                    'nama_akun'          => $akunPeti['nama'],
                    'map'                => 'k',
                    'keterangan'         => $ketPeti,
                    'no_dokumen'         => $nota,
                    'dibuat_oleh'        => $userId,
                ]);

                $this->buatItem($hPetiK->id, [
                    'urut'        => 1,
                    'nama_barang' => 'Peti Kosong',
                    'no_dokumen'  => $nota,
                    'keterangan'  => 'Keluar stok peti ' . $jumlahPeti . ' pcs',
                    'banyak'      => $jumlahPeti,
                    'harga'       => $hargaPeti,
                    'created_by'  => $userId,
                    'updated_by'  => $userId,
                ]);
            }
        });
    }

    // ══════════════════════════════════════════════════════════════
    // RESOLVE KAS — support tunai, transfer, dan split
    // ══════════════════════════════════════════════════════════════

    /**
     * Kembalikan array baris kas yang perlu dibuat:
     * [
     *   ['kode' => '1121-00', 'nama' => 'Kas Tunai Mut', 'proporsi' => 0.4],
     *   ['kode' => '1212-00', 'nama' => 'Bank PT INTAN', 'proporsi' => 0.6],
     * ]
     * proporsi dipakai untuk hitung total_nilai per baris kas.
     */
    private function resolveBarisKas(Penjualan $penjualan, float $totalNilai): array
    {
        $bayarTunai    = (float) ($penjualan->bayar_tunai    ?? 0);
        $bayarTransfer = (float) ($penjualan->bayar_transfer ?? 0);
        $total         = $bayarTunai + $bayarTransfer;

        // Fallback: jika kolom baru belum terisi, pakai metode_pembayaran lama
        if ($total <= 0) {
            $metode = strtolower($penjualan->metode_pembayaran ?? 'tunai');
            $bayarTunai    = $metode !== 'transfer' ? $totalNilai : 0;
            $bayarTransfer = $metode === 'transfer'  ? $totalNilai : 0;
            $total         = $totalNilai;
        }

        $baris = [];

        // ── Baris tunai ──────────────────────────────────────────────────────
        if ($bayarTunai > 0) {
            $akun    = $this->resolveAkun(self::KODE_KAS);
            $baris[] = [
                'kode'     => $akun['kode'],
                'nama'     => $akun['nama'],
                'proporsi' => $bayarTunai / $total,
                'nominal'  => $bayarTunai,
            ];
        }

        // ── Baris transfer ───────────────────────────────────────────────────
        if ($bayarTransfer > 0) {
            $kodeBank = $penjualan->rekeningPerusahaan
                ?->subAnakAkun
                ?->kode_sub_anak_akun;

            if (!$kodeBank) {
                Log::warning(
                    "[JurnalPenjualanTelur] Rekening transfer {$penjualan->no_rekening} " .
                    "belum di-mapping ke akun jurnal. Fallback ke kas tunai. Nota: {$penjualan->no_nota}."
                );
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
    // RESOLVER AKUN dari DB
    // ══════════════════════════════════════════════════════════════

    private function resolveAkun(string $kode): array
    {
        if (isset($this->akunCache[$kode])) {
            return $this->akunCache[$kode];
        }

        $sub = SubAnakAkun::where('kode_sub_anak_akun', $kode)
            ->where('status', 'aktif')->first();
        if ($sub) {
            return $this->akunCache[$kode] = [
                'kode' => $sub->kode_sub_anak_akun,
                'nama' => $sub->nama_sub_anak_akun,
            ];
        }

        $anak = AnakAkun::where('kode_anak_akun', $kode)
            ->where('status', 'aktif')->first();
        if ($anak) {
            return $this->akunCache[$kode] = [
                'kode' => $anak->kode_anak_akun,
                'nama' => $anak->nama_anak_akun,
            ];
        }

        $induk = IndukAkun::where('kode_induk_akun', $kode)
            ->where('status', 'aktif')->first();
        if ($induk) {
            return $this->akunCache[$kode] = [
                'kode' => $induk->kode_induk_akun,
                'nama' => $induk->nama_induk_akun,
            ];
        }

        Log::warning("[JurnalPenjualanTelur] Kode akun '{$kode}' tidak ditemukan di master akun.");

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
            || str_contains($namaLower, '_kg');
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

    private function kodePerJenis(string $namaLower): array
    {
        foreach (self::KODE_PER_JENIS as $keyword => $kode) {
            if (str_contains($namaLower, $keyword)) {
                return $kode;
            }
        }
        return ['4100-01', '6000-01', '1411-00'];
    }

    private function hargaPetiDariDB(): float
    {
        $barang = Barang::where('nama_barang', 'like', '%peti%')
            ->where('nama_barang', 'like', '%kosong%')
            ->first();

        if ($barang && (float) $barang->harga_beli > 0) {
            return (float) $barang->harga_beli;
        }

        return self::HARGA_PETI_DEFAULT;
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