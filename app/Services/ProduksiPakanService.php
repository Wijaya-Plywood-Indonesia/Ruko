<?php

namespace App\Services;

use App\Models\JurnalPembantuHeader;
use App\Models\JurnalPembantuItem;
use App\Models\ProduksiPakan;
use App\Models\Satuan;
use App\Models\SatuanKonversi;
use App\Models\SubAnakAkun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProduksiPakanService
{
    // ── Akun Hardcoded ──────────────────────────────────────────────────────
    const KODE_HUTANG_GAJI        = '2210-01';
    const KODE_HUTANG_LISTRIK     = '2210-02';

    // ── ✨ BARU: Akun Penyeimbang Pendapatan Telur ────────────────────────
    const KODE_PENDAPATAN_TELUR   = '4400-00';

    // Akun Debet Telur (Proses 2) — hardcoded
    const AKUN_TELUR = [
        ['kode' => '1411-00', 'nama' => 'Telur Petian',  'nilai' => 19976],
        ['kode' => '1412-00', 'nama' => 'Telur Kiloan',  'nilai' => 49],
        ['kode' => '1413-00', 'nama' => 'Telur Bentes',  'nilai' => 10000],
    ];

    const NILAI_HUTANG_GAJI    = 650000;
    const NILAI_HUTANG_LISTRIK = 50000;

    private array $akunCache     = [];
    private array $konversiCache = [];

    /* ═══════════════════════════════════════════════════════════════════════
    |  ENTRY POINT
    ═══════════════════════════════════════════════════════════════════════ */

    public function buatJurnalDariProduksi(ProduksiPakan $produksi, int $userId): void
    {
        $produksi->loadMissing([
            'pakanMentahs.barang.subAnakAkun',
            'pakanMentahs.barang.satuan',
            'pakanCampurans.barang.subAnakAkun',
            'pakanCampurans.barang.satuan',
        ]);

        $adaMentah = $produksi->pakanMentahs->contains(
            fn($i) => (float)$i->keluar_pullet > 0
                || (float)$i->keluar_l1     > 0
                || (float)$i->keluar_l2     > 0
        );

        $adaCampuranKeluar = $produksi->pakanCampurans->contains(
            fn($i) => (float)$i->keluar_pullet > 0
                || (float)$i->keluar_l1     > 0
                || (float)$i->keluar_l2     > 0
        );

        DB::transaction(function () use ($produksi, $userId, $adaMentah, $adaCampuranKeluar) {
            if ($adaMentah) {
                $this->buatJurnalProses1($produksi, $userId);
            }

            if ($adaCampuranKeluar) {
                $this->buatJurnalProses2($produksi, $userId);
            }

            if (!$adaMentah && !$adaCampuranKeluar) {
                Log::info("[ProduksiPakan] Tidak ada data terisi, jurnal tidak dibuat.");
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════════════════
    |  PROSES 1 — tidak berubah
    ═══════════════════════════════════════════════════════════════════════ */

    private function buatJurnalProses1(ProduksiPakan $produksi, int $userId): void
    {
        $tgl  = $produksi->tanggal_produksi->toDateString();
        $nota = 'PROD-' . $produksi->id . '-' . $tgl;
        $ket  = "Produksi Pakan | Tgl: {$tgl}";

        $mentahTerpakai = $produksi->pakanMentahs->filter(
            fn($i) => (float)$i->keluar_pullet > 0
                || (float)$i->keluar_l1     > 0
                || (float)$i->keluar_l2     > 0
        );

        $kandangs = [
            'pullet' => ['field' => 'keluar_pullet', 'label' => 'Pullet'],
            'l1'     => ['field' => 'keluar_l1',     'label' => 'Layer 1'],
            'l2'     => ['field' => 'keluar_l2',     'label' => 'Layer 2'],
        ];

        foreach ($kandangs as $kandangKey => $kandang) {
            $field = $kandang['field'];
            $label = $kandang['label'];

            $campuranKandang = $produksi->pakanCampurans->first(function ($item) use ($kandangKey) {
                $nama = strtoupper($item->barang?->nama_barang ?? '');
                return match ($kandangKey) {
                    'pullet' => str_contains($nama, 'PULLET') || str_contains($nama, 'PULET'),
                    'l1'     => str_contains($nama, 'LAYER 1') || str_contains($nama, 'L1'),
                    'l2'     => str_contains($nama, 'LAYER 2') || str_contains($nama, 'L2'),
                    default  => false,
                };
            });

            $totalMasukKandang = $mentahTerpakai->sum(fn($i) => (float)$i->$field);
            if ($totalMasukKandang <= 0) continue;

            $barangCampuran   = $campuranKandang?->barang;
            $kodeAkunCampuran = $barangCampuran?->subAnakAkun?->kode_sub_anak_akun ?? '1500-00';
            $namaAkunCampuran = $this->getNamaAkun($kodeAkunCampuran)
                ?: ($barangCampuran?->nama_barang ?? "Pakan Campuran {$label}");
            $hargaCampuran    = (float)($barangCampuran?->harga_jual ?? 0);
            $nilaiCampuran    = $totalMasukKandang * $hargaCampuran;

            $noJurnal   = $this->nextNoJurnal();
            $ketKandang = "{$ket} | {$label}";

            $hDebit = $this->buatHeader([
                'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                'tgl_transaksi'      => $tgl,
                'jenis_transaksi'    => 'pk',
                'modul_asal'         => 'produksi_pakan',
                'jurnal'             => $noJurnal,
                'no_akun'            => $kodeAkunCampuran,
                'nama_akun'          => $namaAkunCampuran,
                'map'                => 'd',
                'keterangan'         => $ketKandang,
                'no_dokumen'         => $nota,
                'total_nilai'        => $nilaiCampuran,
                'dibuat_oleh'        => $userId,
            ]);

            $this->buatItem($hDebit->id, [
                'urut'        => 1,
                'nama_barang' => $barangCampuran?->nama_barang ?? "Pakan {$label}",
                'no_dokumen'  => $nota,
                'keterangan'  => "Hasil produksi {$label}",
                'banyak'      => $totalMasukKandang,
                'harga'       => $hargaCampuran,
                'jumlah'      => $nilaiCampuran,
                'created_by'  => $userId,
                'updated_by'  => $userId,
            ]);

            $urut = 1;
            foreach ($mentahTerpakai as $mentah) {
                $jumlahKg = (float)$mentah->$field;
                if ($jumlahKg <= 0) continue;

                $barangMentah   = $mentah->barang;
                $kodeAkunMentah = $barangMentah?->subAnakAkun?->kode_sub_anak_akun ?? '1500-01';
                $namaAkunMentah = $this->getNamaAkun($kodeAkunMentah)
                    ?: ($barangMentah?->nama_barang ?? 'Bahan Mentah');
                $hargaMentah    = (float)($barangMentah?->harga_jual ?? 0);
                $nilaiMentah    = $jumlahKg * $hargaMentah;
                $konversi       = $this->getKonversiSak($barangMentah?->id);
                $jumlahSak      = $konversi > 1 ? $jumlahKg / $konversi : $jumlahKg;

                $hKredit = $this->buatHeader([
                    'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'pk',
                    'modul_asal'         => 'produksi_pakan',
                    'jurnal'             => $noJurnal,
                    'no_akun'            => $kodeAkunMentah,
                    'nama_akun'          => $namaAkunMentah,
                    'map'                => 'k',
                    'keterangan'         => $ketKandang,
                    'no_dokumen'         => $nota,
                    'total_nilai'        => $nilaiMentah,
                    'dibuat_oleh'        => $userId,
                ]);

                $this->buatItem($hKredit->id, [
                    'urut'        => $urut++,
                    'nama_barang' => $barangMentah?->nama_barang,
                    'no_dokumen'  => $nota,
                    'keterangan'  => "Bahan {$barangMentah?->nama_barang} → {$label}",
                    'banyak'      => $jumlahSak,
                    'harga'       => $hargaMentah,
                    'jumlah'      => $nilaiMentah,
                    'created_by'  => $userId,
                    'updated_by'  => $userId,
                ]);
            }

            $hGaji = $this->buatHeader([
                'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                'tgl_transaksi'      => $tgl,
                'jenis_transaksi'    => 'pk',
                'modul_asal'         => 'produksi_pakan',
                'jurnal'             => $noJurnal,
                'no_akun'            => self::KODE_HUTANG_GAJI,
                'nama_akun'          => $this->getNamaAkun(self::KODE_HUTANG_GAJI) ?: 'Hutang Gaji Pegawai Kandang',
                'map'                => 'k',
                'keterangan'         => "Hutang Gaji Pegawai | {$ketKandang}",
                'no_dokumen'         => $nota,
                'total_nilai'        => self::NILAI_HUTANG_GAJI,
                'dibuat_oleh'        => $userId,
            ]);

            $this->buatItem($hGaji->id, [
                'urut'        => 1,
                'no_dokumen'  => $nota,
                'keterangan'  => "Akrual gaji pegawai kandang — {$label}",
                'banyak'      => 1,
                'harga'       => self::NILAI_HUTANG_GAJI,
                'jumlah'      => self::NILAI_HUTANG_GAJI,
                'created_by'  => $userId,
                'updated_by'  => $userId,
            ]);

            $hListrik = $this->buatHeader([
                'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                'tgl_transaksi'      => $tgl,
                'jenis_transaksi'    => 'pk',
                'modul_asal'         => 'produksi_pakan',
                'jurnal'             => $noJurnal,
                'no_akun'            => self::KODE_HUTANG_LISTRIK,
                'nama_akun'          => $this->getNamaAkun(self::KODE_HUTANG_LISTRIK) ?: 'Hutang Listrik Kandang',
                'map'                => 'k',
                'keterangan'         => "Hutang Listrik | {$ketKandang}",
                'no_dokumen'         => $nota,
                'total_nilai'        => self::NILAI_HUTANG_LISTRIK,
                'dibuat_oleh'        => $userId,
            ]);

            $this->buatItem($hListrik->id, [
                'urut'        => 1,
                'no_dokumen'  => $nota,
                'keterangan'  => "Akrual beban listrik kandang — {$label}",
                'banyak'      => 1,
                'harga'       => self::NILAI_HUTANG_LISTRIK,
                'jumlah'      => self::NILAI_HUTANG_LISTRIK,
                'created_by'  => $userId,
                'updated_by'  => $userId,
            ]);
        }

        Log::info("[ProduksiPakan] Proses 1 selesai. Produksi ID: {$produksi->id}");
    }

    /* ═══════════════════════════════════════════════════════════════════════
    |  PROSES 2 — ✨ DIPERBARUI: tambah akun 4400-00 sebagai penyeimbang
    |
    |  Alur per kandang:
    |    D: Telur Petian   (hardcoded)
    |    D: Telur Kiloan   (hardcoded)
    |    D: Telur Bentes   (hardcoded)
    |    K: Pakan Campuran (nilai dinamis dari harga barang × qty)
    |    K: Hutang Gaji    (hardcoded)
    |    ?: 4400-00        (penyeimbang — D atau K tergantung selisih)
    |
    |  Rumus penyeimbang:
    |    selisih = totalDebit - totalKredit
    |    selisih > 0  → Debit lebih besar → 4400-00 sebagai KREDIT
    |    selisih < 0  → Kredit lebih besar → 4400-00 sebagai DEBIT
    |    selisih = 0  → Jurnal sudah balance → 4400-00 tidak dibuat
    ═══════════════════════════════════════════════════════════════════════ */

    private function buatJurnalProses2(ProduksiPakan $produksi, int $userId): void
    {
        $tgl  = $produksi->tanggal_produksi->toDateString();
        $nota = 'PRODC-' . $produksi->id . '-' . $tgl;
        $ket  = "Produksi Pakan Campuran | Tgl: {$tgl}";

        $kandangs = [
            'pullet' => ['field' => 'keluar_pullet', 'label' => 'Pullet'],
            'l1'     => ['field' => 'keluar_l1',     'label' => 'Layer 1'],
            'l2'     => ['field' => 'keluar_l2',     'label' => 'Layer 2'],
        ];

        foreach ($produksi->pakanCampurans as $campuran) {
            $nama = strtoupper($campuran->barang?->nama_barang ?? '');

            $kandangKey = match (true) {
                str_contains($nama, 'PULLET') || str_contains($nama, 'PULET') => 'pullet',
                str_contains($nama, 'LAYER 1') || str_contains($nama, 'L1')   => 'l1',
                str_contains($nama, 'LAYER 2') || str_contains($nama, 'L2')   => 'l2',
                default                                                        => null,
            };

            if (!$kandangKey) continue;

            $field        = $kandangs[$kandangKey]['field'];
            $label        = $kandangs[$kandangKey]['label'];
            $jumlahKeluar = (float)$campuran->$field;

            if ($jumlahKeluar <= 0) continue;

            $barangCampuran   = $campuran->barang;
            $kodeAkunCampuran = $barangCampuran?->subAnakAkun?->kode_sub_anak_akun ?? '1500-00';
            $namaAkunCampuran = $this->getNamaAkun($kodeAkunCampuran)
                ?: ($barangCampuran?->nama_barang ?? "Pakan {$label}");
            $hargaCampuran    = (float)($barangCampuran?->harga_jual ?? 0);
            $nilaiCampuran    = $jumlahKeluar * $hargaCampuran;

            $noJurnal   = $this->nextNoJurnal();
            $ketKandang = "{$ket} | {$label}";

            // ─────────────────────────────────────────────────────────────
            // ✨ Hitung total D dan K SEBELUM membuat entri apapun
            //    supaya kita tahu berapa selisihnya dan ke mana arah 4400-00
            // ─────────────────────────────────────────────────────────────
            $totalDebit  = collect(self::AKUN_TELUR)->sum('nilai');     // telur hardcoded
            $totalKredit = $nilaiCampuran + self::NILAI_HUTANG_GAJI;    // pakan + gaji

            // selisih positif  = D lebih besar → 4400-00 harus jadi Kredit
            // selisih negatif  = K lebih besar → 4400-00 harus jadi Debit
            // selisih nol      = sudah balance → tidak perlu 4400-00
            $selisih = $totalDebit - $totalKredit;

            Log::info("[ProduksiPakan] Proses 2 | {$label} | D={$totalDebit} K={$totalKredit} Selisih={$selisih}");

            // ── D: Telur Petian, Kiloan, Bentes (hardcoded) ──────────────
            foreach (self::AKUN_TELUR as $urut => $telur) {
                $hTelur = $this->buatHeader([
                    'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'pk',
                    'modul_asal'         => 'produksi_pakan',
                    'jurnal'             => $noJurnal,
                    'no_akun'            => $telur['kode'],
                    'nama_akun'          => $telur['nama'],
                    'map'                => 'd',
                    'keterangan'         => $ketKandang,
                    'no_dokumen'         => $nota,
                    'total_nilai'        => $telur['nilai'],
                    'dibuat_oleh'        => $userId,
                ]);

                $this->buatItem($hTelur->id, [
                    'urut'        => $urut + 1,
                    'nama_barang' => $telur['nama'],
                    'no_dokumen'  => $nota,
                    'keterangan'  => "{$telur['nama']} dari produksi {$label}",
                    'banyak'      => 1,
                    'harga'       => $telur['nilai'],
                    'jumlah'      => $telur['nilai'],
                    'created_by'  => $userId,
                    'updated_by'  => $userId,
                ]);
            }

            // ── K: Pakan Campuran keluar ─────────────────────────────────
            $hCampuran = $this->buatHeader([
                'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                'tgl_transaksi'      => $tgl,
                'jenis_transaksi'    => 'pk',
                'modul_asal'         => 'produksi_pakan',
                'jurnal'             => $noJurnal,
                'no_akun'            => $kodeAkunCampuran,
                'nama_akun'          => $namaAkunCampuran,
                'map'                => 'k',
                'keterangan'         => $ketKandang,
                'no_dokumen'         => $nota,
                'total_nilai'        => $nilaiCampuran,
                'dibuat_oleh'        => $userId,
            ]);

            $this->buatItem($hCampuran->id, [
                'urut'        => 1,
                'nama_barang' => $barangCampuran?->nama_barang,
                'no_dokumen'  => $nota,
                'keterangan'  => "Pakan {$label} keluar ke kandang",
                'banyak'      => $jumlahKeluar,
                'harga'       => $hargaCampuran,
                'jumlah'      => $nilaiCampuran,
                'created_by'  => $userId,
                'updated_by'  => $userId,
            ]);

            // ── K: Hutang Gaji Karyawan ──────────────────────────────────
            $hGaji = $this->buatHeader([
                'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                'tgl_transaksi'      => $tgl,
                'jenis_transaksi'    => 'pk',
                'modul_asal'         => 'produksi_pakan',
                'jurnal'             => $noJurnal,
                'no_akun'            => self::KODE_HUTANG_GAJI,
                'nama_akun'          => $this->getNamaAkun(self::KODE_HUTANG_GAJI) ?: 'Hutang Gaji Karyawan',
                'map'                => 'k',
                'keterangan'         => "Hutang Gaji Karyawan | {$ketKandang}",
                'no_dokumen'         => $nota,
                'total_nilai'        => self::NILAI_HUTANG_GAJI,
                'dibuat_oleh'        => $userId,
            ]);

            $this->buatItem($hGaji->id, [
                'urut'        => 1,
                'no_dokumen'  => $nota,
                'keterangan'  => "Akrual gaji karyawan kandang — {$label}",
                'banyak'      => 1,
                'harga'       => self::NILAI_HUTANG_GAJI,
                'jumlah'      => self::NILAI_HUTANG_GAJI,
                'created_by'  => $userId,
                'updated_by'  => $userId,
            ]);

            // ─────────────────────────────────────────────────────────────
            // ✨ PENYEIMBANG: Akun 4400-00 Pendapatan Telur
            //
            //    Kita masuk ke sini hanya jika selisih != 0.
            //    Nilai yang dimasukkan adalah abs(selisih) karena map
            //    (D atau K) sudah menentukan sisi mana yang bertambah.
            // ─────────────────────────────────────────────────────────────
            if ($selisih != 0) {
                // Tentukan posisi akun 4400-00
                // Debit lebih besar (selisih > 0) → 4400-00 jadi Kredit untuk menutupnya
                // Kredit lebih besar (selisih < 0) → 4400-00 jadi Debit untuk menutupnya
                $mapPendapatan   = $selisih > 0 ? 'k' : 'd';
                $nilaiPenyeimbang = abs($selisih);

                $namaPendapatan = $this->getNamaAkun(self::KODE_PENDAPATAN_TELUR)
                    ?: 'Pendapatan Telur';

                $keteranganArah = $selisih > 0
                    ? "Pendapatan telur (penyeimbang kredit) — {$label}"
                    : "Pendapatan telur (penyeimbang debit) — {$label}";

                $hPendapatan = $this->buatHeader([
                    'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'pk',
                    'modul_asal'         => 'produksi_pakan',
                    'jurnal'             => $noJurnal,
                    'no_akun'            => self::KODE_PENDAPATAN_TELUR,
                    'nama_akun'          => $namaPendapatan,
                    'map'                => $mapPendapatan,
                    'keterangan'         => $keteranganArah,
                    'no_dokumen'         => $nota,
                    'total_nilai'        => $nilaiPenyeimbang,
                    'dibuat_oleh'        => $userId,
                ]);

                $this->buatItem($hPendapatan->id, [
                    'urut'        => 1,
                    'nama_barang' => $namaPendapatan,
                    'no_dokumen'  => $nota,
                    'keterangan'  => $keteranganArah,
                    'banyak'      => 1,
                    'harga'       => $nilaiPenyeimbang,
                    'jumlah'      => $nilaiPenyeimbang,
                    'created_by'  => $userId,
                    'updated_by'  => $userId,
                ]);

                Log::info(
                    "[ProduksiPakan] Akun 4400-00 ditambahkan sebagai "
                        . strtoupper($mapPendapatan)
                        . " senilai {$nilaiPenyeimbang} | {$label}"
                );
            } else {
                Log::info("[ProduksiPakan] Jurnal {$label} sudah balance, 4400-00 tidak dibuat.");
            }
        }

        Log::info("[ProduksiPakan] Proses 2 selesai. Produksi ID: {$produksi->id}");
    }

    /* ═══════════════════════════════════════════════════════════════════════
    |  HELPERS — tidak berubah
    ═══════════════════════════════════════════════════════════════════════ */

    private function getKonversiSak(?int $barangId): float
    {
        if (!$barangId) return 1;
        if (isset($this->konversiCache[$barangId])) return $this->konversiCache[$barangId];

        $satuanSak = Satuan::whereRaw('LOWER(nama_satuan) = ?', ['sak'])->first();
        if (!$satuanSak) return $this->konversiCache[$barangId] = 1;

        $konversi = SatuanKonversi::where('id_barang', $barangId)
            ->where('id_satuan_asal', $satuanSak->id)
            ->aktif()
            ->first();

        return $this->konversiCache[$barangId] = $konversi ? (float)$konversi->nilai_konversi : 1;
    }

    private function getNamaAkun(string $kode): string
    {
        if (isset($this->akunCache[$kode])) return $this->akunCache[$kode];

        return $this->akunCache[$kode] = SubAnakAkun::where('kode_sub_anak_akun', $kode)
            ->value('nama_sub_anak_akun') ?? '';
    }

    private function nextNoJurnal(): int
    {
        return JurnalPembantuHeader::lockForUpdate()->max('jurnal') + 1;
    }

    private function nextNomorPembantu(): int
    {
        return JurnalPembantuHeader::lockForUpdate()->max('no_jurnal_pembantu') + 1;
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
}
