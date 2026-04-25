<?php

namespace App\Services;

use App\Models\AnakAkun;
use App\Models\IndukAkun;
use App\Models\JurnalPembantuHeader;
use App\Models\JurnalPembantuItem;
use App\Models\Penjualan;
use App\Models\SubAnakAkun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service Jurnal Pembantu — Penjualan Telur  (v3 final)
 *
 * STRUKTUR JURNAL (1 nota = 1 no_jurnal):
 * ─────────────────────────────────────────
 *  D  1121-00   Kas Tunai Mut
 *  K  4100-01   Penjualan Telur Petian Ruko   ← sesuai jenis
 *  K  4100-02   Penjualan Telur Kiloan Ruko   ← sesuai jenis
 *  K  4100-03   Penjualan Telur Bentes Ruko   ← sesuai jenis
 *  D  6000-01   HPP Telor (petian/kiloan)     ← sesuai jenis
 *  D  6000-02   HPP Bentes                    ← sesuai jenis
 *  K  1411-00   Persediaan Telur Petian RUKO  ← sesuai jenis
 *  K  1412-00   Persediaan Telur Kiloan RUKO  ← sesuai jenis
 *  K  1413-00   Persediaan Telur Bentes RUKO  ← sesuai jenis
 *
 *  (Peti berbayar → no_jurnal terpisah)
 *  D  1121-00   Kas Tunai Mut
 *  K  1600-01   Peti Kosong
 */
class JurnalPenjualanTelurService
{
    // ══════════════════════════════════════════════════════════════
    // KODE AKUN — sesuai kode_sub_anak_akun di DB
    // Ubah nilai ini jika kode di master akun berubah.
    // ══════════════════════════════════════════════════════════════

    /** Kas masuk — satu akun untuk semua toko (INA / Mut) */
    const KODE_KAS  = '1121-00';

    /** Peti kosong */
    const KODE_PETI = '1600-01';

    /**
     * Mapping nama_barang → ['kode_pendapatan', 'kode_hpp', 'kode_persediaan']
     *
     * Kode dari DB sub_anak_akuns:
     *   Pendapatan : 4100-01 petian Ruko | 4100-02 kiloan Ruko | 4100-03 bentes Ruko
     *   HPP        : 6000-01 hpp telor   | 6000-02 hpp bentes
     *   Persediaan : 1411-00 petian      | 1412-00 kiloan      | 1413-00 bentes
     *
     * Urutan dari atas = lebih spesifik dulu (bentes harus sebelum telur).
     */
    const KODE_PER_JENIS = [
        // ── Bentes (harus di atas sebelum keyword 'telur' generik) ───────────
        'bentes'      => ['4100-03', '6000-02', '1413-00'],

        // ── Kiloan / kg ───────────────────────────────────────────────────────
        'telur_kilo'  => ['4100-02', '6000-01', '1412-00'],
        'telur kilo'  => ['4100-02', '6000-01', '1412-00'],
        'telur ruko'  => ['4100-02', '6000-01', '1412-00'],
        'telur_ruko'  => ['4100-02', '6000-01', '1412-00'],
        '_kg'         => ['4100-02', '6000-01', '1412-00'],
        '_kilo'       => ['4100-02', '6000-01', '1412-00'],

        // ── Butiran / petian ──────────────────────────────────────────────────
        'telur_butir' => ['4100-01', '6000-01', '1411-00'],
        'telur butir' => ['4100-01', '6000-01', '1411-00'],
        '_butir'      => ['4100-01', '6000-01', '1411-00'],

        // ── Fallback generik → petian ─────────────────────────────────────────
        'telur'       => ['4100-01', '6000-01', '1411-00'],
    ];

    // ══════════════════════════════════════════════════════════════
    // Cache agar tidak query DB berulang dalam 1 request
    // ══════════════════════════════════════════════════════════════

    private array $akunCache = [];

    // ══════════════════════════════════════════════════════════════

    /**
     * Entry point utama.
     * Dipanggil dari PenjualansTable saat validasi → LUNAS.
     */
    public function buatJurnalDariPenjualan(Penjualan $penjualan, int $userId): void
    {
        $penjualan->loadMissing(['details.barang']);

        $itemTelur = collect();
        $itemPeti  = collect();

        foreach ($penjualan->details as $detail) {
            $nama = strtolower($detail->nama_barang ?? '');

            if ($this->isTelur($nama)) {
                $itemTelur->push($detail);
            } elseif ($this->isPeti($nama)) {
                $itemPeti->push($detail);
            }
            // lainnya (triplek, dll.) → diabaikan
        }

        if ($itemTelur->isEmpty() && $itemPeti->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($penjualan, $itemTelur, $itemPeti, $userId) {

            $tgl      = $penjualan->tanggal->toDateString();
            $nota     = $penjualan->no_nota;
            $customer = $penjualan->nama_customer ?: 'Pelanggan';

            $totalTelur = $itemTelur->sum('subtotal');
            $totalHpp   = $itemTelur->sum(
                fn($d) => (float) $d->qty * (float) ($d->barang->harga_beli ?? 0)
            );

            // Satu no_jurnal untuk seluruh penjualan + HPP
            $noJurnal = $this->nextNomorJurnal();

            // ─── D : Kas Tunai Mut ────────────────────────────────────────────
            if ($totalTelur > 0) {
                $akunKas = $this->resolveAkun(self::KODE_KAS);
                $ketJual = $this->ket('Penjualan Telur', $nota, $customer);

                $hKas = $this->buatHeader([
                    'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'bk',
                    'modul_asal'         => 'penjualan_telur',
                    'jurnal'             => $noJurnal,
                    'no_akun'            => $akunKas['kode'],
                    'nama_akun'          => $akunKas['nama'],
                    'map'                => 'd',
                    'keterangan'         => $ketJual,
                    'no_dokumen'         => $nota,
                    'dibuat_oleh'        => $userId,
                ]);

                $urut = 1;
                foreach ($itemTelur as $d) {
                    $this->buatItem($hKas->id, [
                        'urut'        => $urut++,
                        'jenis_pihak' => 'pelanggan',
                        'nama_pihak'  => $customer,
                        'nama_barang' => $d->nama_barang,
                        'no_dokumen'  => $nota,
                        'no_referensi'=> (string) $d->id,
                        'keterangan'  => $d->nama_barang . ' ' . $d->qty . ' ' . ($d->satuan ?? ''),
                        'banyak'      => $d->qty,
                        'harga'       => $d->harga_jual,
                        'created_by'  => $userId,
                        'updated_by'  => $userId,
                    ]);
                }

                // ─── K : Pendapatan (per jenis telur) ────────────────────────
                // Kelompok per kode_pendapatan
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
                            'urut'        => $urut++,
                            'jenis_pihak' => 'pelanggan',
                            'nama_pihak'  => $customer,
                            'nama_barang' => $d->nama_barang,
                            'no_dokumen'  => $nota,
                            'no_referensi'=> (string) $d->id,
                            'keterangan'  => $d->nama_barang . ' ' . $d->qty . ' ' . ($d->satuan ?? ''),
                            'banyak'      => $d->qty,
                            'harga'       => $d->harga_jual,
                            'created_by'  => $userId,
                            'updated_by'  => $userId,
                        ]);
                    }
                }
            }

            // ─── D : HPP  &  K : Persediaan (per jenis telur, no_jurnal sama) ─
            if ($totalHpp > 0) {
                $ketHpp = $this->ket('HPP Penjualan Telur', $nota);

                // Kelompok per pasangan [kode_hpp, kode_persediaan]
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
                    $hHpp = $this->buatHeader([
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
                            'urut'        => $urut++,
                            'nama_barang' => $d->nama_barang,
                            'no_dokumen'  => $nota,
                            'no_referensi'=> (string) $d->id,
                            'keterangan'  => 'HPP ' . $d->nama_barang,
                            'banyak'      => $d->qty,
                            'harga'       => $d->barang->harga_beli,
                            'created_by'  => $userId,
                            'updated_by'  => $userId,
                        ]);
                    }

                    // K: Persediaan
                    $akunPers = $this->resolveAkun($kodePers);
                    $hPers = $this->buatHeader([
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
                            'urut'        => $urut++,
                            'nama_barang' => $d->nama_barang,
                            'no_dokumen'  => $nota,
                            'no_referensi'=> (string) $d->id,
                            'keterangan'  => 'Keluar stok ' . $d->nama_barang,
                            'banyak'      => $d->qty,
                            'harga'       => $d->barang->harga_beli,
                            'created_by'  => $userId,
                            'updated_by'  => $userId,
                        ]);
                    }
                }
            }

            // ─── PETI (no_jurnal baru — transaksi terpisah) ───────────────────
            $itemPetiBayar = $itemPeti->filter(fn($d) => (float) $d->subtotal > 0);

            if ($itemPetiBayar->isNotEmpty()) {
                $noJurnalPeti = $this->nextNomorJurnal();
                $ketPeti      = $this->ket('Penjualan Peti', $nota, $customer);
                $akunKas      = $this->resolveAkun(self::KODE_KAS);
                $akunPeti     = $this->resolveAkun(self::KODE_PETI);

                // D: Kas
                $hKasPeti = $this->buatHeader([
                    'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'bk',
                    'modul_asal'         => 'penjualan_telur',
                    'jurnal'             => $noJurnalPeti,
                    'no_akun'            => $akunKas['kode'],
                    'nama_akun'          => $akunKas['nama'],
                    'map'                => 'd',
                    'keterangan'         => $ketPeti,
                    'no_dokumen'         => $nota,
                    'dibuat_oleh'        => $userId,
                ]);

                $urut = 1;
                foreach ($itemPetiBayar as $d) {
                    $this->buatItem($hKasPeti->id, [
                        'urut'        => $urut++,
                        'jenis_pihak' => 'pelanggan',
                        'nama_pihak'  => $customer,
                        'nama_barang' => $d->nama_barang,
                        'no_dokumen'  => $nota,
                        'no_referensi'=> (string) $d->id,
                        'keterangan'  => $d->nama_barang . ' ' . $d->qty . ' pcs',
                        'banyak'      => $d->qty,
                        'harga'       => $d->harga_jual,
                        'created_by'  => $userId,
                        'updated_by'  => $userId,
                    ]);
                }

                // K: Peti Kosong
                $hPetiK = $this->buatHeader([
                    'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'bk',
                    'modul_asal'         => 'penjualan_telur',
                    'jurnal'             => $noJurnalPeti,
                    'no_akun'            => $akunPeti['kode'],
                    'nama_akun'          => $akunPeti['nama'],
                    'map'                => 'k',
                    'keterangan'         => $ketPeti,
                    'no_dokumen'         => $nota,
                    'dibuat_oleh'        => $userId,
                ]);

                $urut = 1;
                foreach ($itemPetiBayar as $d) {
                    $this->buatItem($hPetiK->id, [
                        'urut'        => $urut++,
                        'nama_barang' => $d->nama_barang,
                        'no_dokumen'  => $nota,
                        'no_referensi'=> (string) $d->id,
                        'keterangan'  => 'Keluar stok ' . $d->nama_barang,
                        'banyak'      => $d->qty,
                        'harga'       => $d->harga_jual,
                        'created_by'  => $userId,
                        'updated_by'  => $userId,
                    ]);
                }
            }
        });
    }

    // ══════════════════════════════════════════════════════════════
    // RESOLVER AKUN dari DB: sub_anak_akuns → anak_akuns → induk_akuns
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

    private function isPeti(string $namaLower): bool
    {
        return str_contains($namaLower, 'peti')
            || str_contains($namaLower, 'kotak');
    }

    /** Kembalikan ['kode_pendapatan', 'kode_hpp', 'kode_persediaan'] */
    private function kodePerJenis(string $namaLower): array
    {
        foreach (self::KODE_PER_JENIS as $keyword => $kode) {
            if (str_contains($namaLower, $keyword)) {
                return $kode;
            }
        }
        return ['4100-01', '6000-01', '1411-00']; // fallback petian
    }

    /** Format: "Penjualan Telur | No.Nota: WJY-008619 | Rafa" */
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
