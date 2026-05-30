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
    const KODE_HUTANG_GAJI    = '2210-01';
    const KODE_HUTANG_LISTRIK = '2210-02';

    private array $akunCache      = [];
    private array $konversiCache  = [];  // ✅ cache konversi agar tidak query berulang

    public function buatJurnalDariProduksi(ProduksiPakan $produksi, int $userId): void
    {
        $produksi->loadMissing([
            'pakanMentahs.barang.subAnakAkun',
            'pakanMentahs.barang.satuan',        // ✅ tambahan untuk cek satuan
            'pakanCampurans.barang.subAnakAkun',
        ]);

        $mentahTerpakai = $produksi->pakanMentahs->filter(
            fn($item) =>
            (float) $item->keluar_pullet > 0 ||
                (float) $item->keluar_l1     > 0 ||
                (float) $item->keluar_l2     > 0
        );

        $campuranDiproduksi = $produksi->pakanCampurans->filter(
            fn($item) => (float) $item->masuk > 0
        );

        if ($mentahTerpakai->isEmpty() && $campuranDiproduksi->isEmpty()) {
            Log::info("[JurnalProduksiPakan] Tidak ada data terpakai, jurnal tidak dibuat.");
            return;
        }

        DB::transaction(function () use ($produksi, $mentahTerpakai, $campuranDiproduksi, $userId) {

            $tgl  = $produksi->tanggal_produksi->toDateString();
            $nota = 'PROD-' . $produksi->id . '-' . $tgl;
            $ket  = "Produksi Pakan | Tgl: {$tgl}";

            foreach ($campuranDiproduksi as $campuran) {
                $barangCampuran   = $campuran->barang;
                $kodeAkunCampuran = $barangCampuran?->subAnakAkun?->kode_sub_anak_akun ?? '1500-00';
                $namaAkunCampuran = $this->getNamaAkun($kodeAkunCampuran) ?: ($barangCampuran?->nama_barang ?? 'Pakan Campuran');
                $totalMasuk       = (float) $campuran->masuk;  // dalam kg

                if ($totalMasuk <= 0) continue;

                $namaCampuran = strtoupper($barangCampuran?->nama_barang ?? '');
                $fieldKeluar  = match (true) {
                    str_contains($namaCampuran, 'PULLET') || str_contains($namaCampuran, 'PULET')  => 'keluar_pullet',
                    str_contains($namaCampuran, 'LAYER 1') || str_contains($namaCampuran, 'L1')    => 'keluar_l1',
                    str_contains($namaCampuran, 'LAYER 2') || str_contains($namaCampuran, 'L2')    => 'keluar_l2',
                    default                                                                         => null,
                };

                $noJurnalCampuran = JurnalPembantuHeader::lockForUpdate()->max('jurnal') + 1;
                $ketCampuran      = $ket . ' | ' . $barangCampuran?->nama_barang;

                $hargaCampuran = (float) ($barangCampuran?->harga_jual ?? 0);
                $nilaiCampuran = $totalMasuk * $hargaCampuran;

                // ── D: Pakan Campuran Bertambah ──
                $hDebit = $this->buatHeader([
                    'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'pk',
                    'modul_asal'         => 'produksi_pakan',
                    'jurnal'             => $noJurnalCampuran,
                    'no_akun'            => $kodeAkunCampuran,
                    'nama_akun'          => $namaAkunCampuran,
                    'map'                => 'd',
                    'keterangan'         => $ketCampuran,
                    'no_dokumen'         => $nota,
                    'total_nilai'        => $nilaiCampuran,
                    'dibuat_oleh'        => $userId,
                ]);

                $this->buatItem($hDebit->id, [
                    'urut'        => 1,
                    'nama_barang' => $barangCampuran?->nama_barang,
                    'no_dokumen'  => $nota,
                    'keterangan'  => 'Masuk produksi ' . $barangCampuran?->nama_barang,
                    'banyak'      => $totalMasuk,
                    'harga'       => $hargaCampuran,
                    'jumlah'      => $nilaiCampuran,
                    'created_by'  => $userId,
                    'updated_by'  => $userId,
                ]);

                // ── K: Bahan Mentah Berkurang ──
                foreach ($mentahTerpakai as $mentah) {
                    if (!$fieldKeluar) continue;

                    $jumlahKeluarKg = (float) $mentah->$fieldKeluar;
                    if ($jumlahKeluarKg <= 0) continue;

                    $barangMentah   = $mentah->barang;
                    $kodeAkunMentah = $barangMentah?->subAnakAkun?->kode_sub_anak_akun ?? '1500-01';
                    $namaAkunMentah = $this->getNamaAkun($kodeAkunMentah) ?: ($barangMentah?->nama_barang ?? 'Bahan Mentah');

                    // ✅ Konversi balik kg → sak untuk item banyak & nilai
                    $konversi       = $this->getKonversiSak($barangMentah?->id);
                    $jumlahKeluarSak = $konversi > 1
                        ? $jumlahKeluarKg / $konversi   // 450 kg / 50 = 9 sak
                        : $jumlahKeluarKg;              // sudah kg, tidak perlu konversi

                    $hargaMentah = (float) ($barangMentah?->harga_jual ?? 0);

                    // ✅ nilai = jumlah_sak × harga_per_sak
                    //    (atau jumlah_kg × harga_per_kg jika tidak ada konversi)
                    $nilaiMentah = $jumlahKeluarKg * $hargaMentah;

                    $hKredit = $this->buatHeader([
                        'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                        'tgl_transaksi'      => $tgl,
                        'jenis_transaksi'    => 'pk',
                        'modul_asal'         => 'produksi_pakan',
                        'jurnal'             => $noJurnalCampuran,
                        'no_akun'            => $kodeAkunMentah,
                        'nama_akun'          => $namaAkunMentah,
                        'map'                => 'k',
                        'keterangan'         => $ketCampuran,
                        'no_dokumen'         => $nota,
                        'total_nilai'        => $nilaiMentah,
                        'dibuat_oleh'        => $userId,
                    ]);

                    $this->buatItem($hKredit->id, [
                        'urut'        => 1,
                        'nama_barang' => $barangMentah?->nama_barang,
                        'no_dokumen'  => $nota,
                        'keterangan'  => 'Keluar ke ' . $barangCampuran?->nama_barang,
                        'banyak'      => $jumlahKeluarKg,  // ✅ dalam kg (satuan asli)
                        'harga'       => $hargaMentah,       // ✅ harga per kg
                        'jumlah'      => $nilaiMentah,       // ✅ kg × harga
                        'created_by'  => $userId,
                        'updated_by'  => $userId,
                    ]);
                }

                // ── K: Hutang Gaji ──
                $hGaji = $this->buatHeader([
                    'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'pk',
                    'modul_asal'         => 'produksi_pakan',
                    'jurnal'             => $noJurnalCampuran,
                    'no_akun'            => self::KODE_HUTANG_GAJI,
                    'nama_akun'          => $this->getNamaAkun(self::KODE_HUTANG_GAJI) ?: 'Hutang Gaji Pegawai Kandang',
                    'map'                => 'k',
                    'keterangan'         => "Hutang Gaji Pegawai Kandang | {$ketCampuran}",
                    'no_dokumen'         => $nota,
                    'total_nilai'        => 650000,
                    'dibuat_oleh'        => $userId,
                ]);

                $this->buatItem($hGaji->id, [
                    'urut'        => 1,
                    'no_dokumen'  => $nota,
                    'keterangan'  => 'Akrual gaji pegawai kandang — ' . $barangCampuran?->nama_barang,
                    'banyak'      => 1,
                    'harga'       => 650000,
                    'jumlah'      => 650000,
                    'created_by'  => $userId,
                    'updated_by'  => $userId,
                ]);

                // ── K: Hutang Listrik ──
                $hListrik = $this->buatHeader([
                    'no_jurnal_pembantu' => $this->nextNomorPembantu(),
                    'tgl_transaksi'      => $tgl,
                    'jenis_transaksi'    => 'pk',
                    'modul_asal'         => 'produksi_pakan',
                    'jurnal'             => $noJurnalCampuran,
                    'no_akun'            => self::KODE_HUTANG_LISTRIK,
                    'nama_akun'          => $this->getNamaAkun(self::KODE_HUTANG_LISTRIK) ?: 'Hutang Listrik',
                    'map'                => 'k',
                    'keterangan'         => "Hutang Listrik Kandang | {$ketCampuran}",
                    'no_dokumen'         => $nota,
                    'total_nilai'        => 50000,
                    'dibuat_oleh'        => $userId,
                ]);

                $this->buatItem($hListrik->id, [
                    'urut'        => 1,
                    'no_dokumen'  => $nota,
                    'keterangan'  => 'Akrual beban listrik kandang — ' . $barangCampuran?->nama_barang,
                    'banyak'      => 1,
                    'harga'       => 50000,
                    'jumlah'      => 50000,
                    'created_by'  => $userId,
                    'updated_by'  => $userId,
                ]);
            }

            Log::info("[JurnalProduksiPakan] Jurnal berhasil dibuat. Produksi ID: {$produksi->id}");
        });
    }

    // ══════════════════════════════════════════════════════════════
    // HELPER
    // ══════════════════════════════════════════════════════════════

    /**
     * Ambil nilai konversi sak → kg untuk barang tertentu.
     * Sama persis dengan getKonversiSak() di ProduksiPakanLaporan.
     * Return 1 jika barang tidak memiliki konversi sak (artinya sudah dalam kg).
     */
    private function getKonversiSak(?int $barangId): float
    {
        if (!$barangId) return 1;

        // Gunakan cache agar tidak query DB berulang dalam satu transaksi
        if (isset($this->konversiCache[$barangId])) {
            return $this->konversiCache[$barangId];
        }

        $satuanSak = Satuan::whereRaw('LOWER(nama_satuan) = ?', ['sak'])->first();
        if (!$satuanSak) {
            return $this->konversiCache[$barangId] = 1;
        }

        $konversi = SatuanKonversi::where('id_barang', $barangId)
            ->where('id_satuan_asal', $satuanSak->id)
            ->aktif()
            ->first();

        return $this->konversiCache[$barangId] = $konversi
            ? (float) $konversi->nilai_konversi
            : 1;
    }

    private function getNamaAkun(string $kode): string
    {
        if (isset($this->akunCache[$kode])) {
            return $this->akunCache[$kode];
        }

        return $this->akunCache[$kode] = SubAnakAkun::where('kode_sub_anak_akun', $kode)
            ->value('nama_sub_anak_akun') ?? '';
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

    private function nextNomorPembantu(): int
    {
        return JurnalPembantuHeader::lockForUpdate()->max('no_jurnal_pembantu') + 1;
    }
}
