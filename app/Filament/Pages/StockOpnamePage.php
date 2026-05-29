<?php

namespace App\Filament\Pages;

use App\Models\IdentitasToko;
use App\Models\Barang;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\StokBarangToko;
use App\Services\StockOpnameService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class StockOpnamePage extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;
    public static ?string $navigationLabel = 'Stock Opname';
    protected static string|UnitEnum|null $navigationGroup = 'Stock Barang';
    protected static ?int $navigationSort = 10;

    public function getView(): string
    {
        return 'filament.pages.stock-opname';
    }

    /* =========================
     |  STATE
     ========================= */

    public ?int $toko_id = null;
    public ?string $catatan = null;

    public ?StockOpname $opname = null;
    public array $details = [];

    public ?string $catatan_approval = null;

    /** Daftar opname yang sedang berjalan (draft / menunggu / ditolak) */
    public array $daftarOpname = [];

    /** Riwayat opname yang sudah selesai */
    public array $riwayatOpname = [];

    // ── Filter riwayat ──────────────────────────────────────────────
    public ?string $filterTanggalDari = null;
    public ?string $filterTanggalSampai = null;
    public ?string $filterStatus = null;  // null = semua, atau salah satu status

    /* =========================
     |  MOUNT
     ========================= */

    public function mount(): void
    {
        // Default filter: bulan berjalan
        $this->filterTanggalDari = now()->startOfMonth()->format('Y-m-d');
        $this->filterTanggalSampai = now()->endOfMonth()->format('Y-m-d');

        $this->form->fill();
        $this->refreshDaftarOpname();
        $this->refreshRiwayatOpname();
    }

    /* =========================
     |  FORM
     ========================= */

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('toko_id')
                ->label('Pilih Toko')
                ->options(
                    IdentitasToko::where('status', 'aktif')
                        ->pluck('nama_toko', 'id')
                )
                ->required()
                ->searchable()
                ->placeholder('Pilih toko untuk memulai opname'),

            Textarea::make('catatan')
                ->label('Catatan Opname')
                ->placeholder('Opsional')
                ->rows(2),
        ]);
    }

    /* =========================
     |  REFRESH RIWAYAT OPNAME
     ========================= */

    public function refreshRiwayatOpname(): void
    {
        $query = StockOpname::with(['toko', 'createdBy', 'approvedBy'])
            ->where('status', 'disetujui');

        // Filter tanggal opname
        if ($this->filterTanggalDari) {
            $query->whereDate('tanggal_opname', '>=', $this->filterTanggalDari);
        }
        if ($this->filterTanggalSampai) {
            $query->whereDate('tanggal_opname', '<=', $this->filterTanggalSampai);
        }

        $rows = $query->latest('approved_at')->limit(50)->get();

        $this->riwayatOpname = $rows->map(fn($o) => [
            'id' => $o->id,
            'no_opname' => $o->no_opname,
            'toko' => $o->toko->nama_toko ?? '-',
            'tanggal' => $o->tanggal_opname->format('d-m-Y'),
            'approved_by' => $o->approvedBy->name ?? '-',
            'approved_at' => $o->approved_at?->format('d-m-Y H:i') ?? '-',
            'created_by' => $o->createdBy->name ?? '-',
        ])->toArray();
    }

    public function refreshDaftarOpname(): void
    {
        $query = StockOpname::with(['toko', 'createdBy'])
            ->whereIn('status', ['draft', 'menunggu', 'ditolak']);

        // Terapkan filter status jika dipilih (hanya untuk status berjalan)
        if ($this->filterStatus && in_array($this->filterStatus, ['draft', 'menunggu', 'ditolak'])) {
            $query->where('status', $this->filterStatus);
        }

        // Filter tanggal pada daftar berjalan
        if ($this->filterTanggalDari) {
            $query->whereDate('tanggal_opname', '>=', $this->filterTanggalDari);
        }
        if ($this->filterTanggalSampai) {
            $query->whereDate('tanggal_opname', '<=', $this->filterTanggalSampai);
        }

        $rows = $query->latest()->get();

        $this->daftarOpname = $rows->map(fn($o) => [
            'id' => $o->id,
            'no_opname' => $o->no_opname,
            'toko' => $o->toko->nama_toko ?? '-',
            'tanggal' => $o->tanggal_opname->format('d-m-Y'),
            'status' => $o->status,
            'created_by' => $o->createdBy->name ?? '-',
            'catatan' => $o->catatan ?? '',
        ])->toArray();
    }

    public function terapkanFilter(): void
    {
        $this->refreshDaftarOpname();
        $this->refreshRiwayatOpname();
    }

    public function resetFilter(): void
    {
        $this->filterTanggalDari = now()->startOfMonth()->format('Y-m-d');
        $this->filterTanggalSampai = now()->endOfMonth()->format('Y-m-d');
        $this->filterStatus = null;

        $this->refreshDaftarOpname();
        $this->refreshRiwayatOpname();
    }

    /* =========================
     |  BUKA OPNAME YANG ADA
     ========================= */

    public function bukaOpname(int $id): void
    {
        $found = StockOpname::find($id);

        if (!$found) {
            Notification::make()->title('Opname tidak ditemukan')->danger()->send();
            return;
        }

        $this->opname = $found;
        $this->loadDetails();
    }

    /* =========================
     |  MULAI OPNAME
     ========================= */

    public function mulaiOpname(): void
    {
        $state = $this->form->getState();
        $tokoId = $state['toko_id'] ?? null;

        if (!$tokoId) {
            Notification::make()->title('Pilih toko terlebih dahulu')->danger()->send();
            return;
        }

        // Lanjutkan jika ada opname draft/menunggu yang belum selesai
        $existing = StockOpname::where('toko_id', $tokoId)
            ->whereIn('status', ['draft', 'menunggu'])
            ->latest()
            ->first();

        if ($existing) {
            $this->opname = $existing;
        } else {
            $this->opname = StockOpname::create([
                'toko_id' => $tokoId,
                'tanggal_opname' => today(),
                'catatan' => $state['catatan'] ?? null,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            // Load semua barang aktif yang terhubung dengan akun jurnal (sama seperti Stok Matrix)
            $barangs = Barang::with(['subAnakAkun', 'satuan'])
                ->whereHas('subAnakAkun', function ($query) {
                    $query->whereNotNull('kode_sub_anak_akun')
                        ->where('kode_sub_anak_akun', '!=', '');
                })
                ->orderBy('nama_barang')
                ->get();

            foreach ($barangs as $barang) {
                // Sisa Stok dihitung dari total saldo berjalan (debet - kredit) Buku Besar JurnalUmum
                $qtyJurnal = (float) ($barang->stok_buku_besar ?? 0.0);

                StockOpnameDetail::create([
                    'stock_opname_id' => $this->opname->id,
                    'barang_id' => $barang->id,
                    'stok_sistem' => $qtyJurnal,
                    'stok_aktual' => null,
                    'selisih' => null,
                ]);
            }
        }

        $this->loadDetails();
    }

    /* =========================
     |  LOAD DETAIL
     ========================= */

    public function loadDetails(): void
    {
        if (!$this->opname)
            return;

        // 💡 JIKA MASIH DRAFT: Sinkronkan barang-barang baru yang baru dihubungkan ke Jurnal secara dinamis
        if ($this->opname->isDraft()) {
            DB::transaction(function () {
                $barangs = Barang::whereHas('subAnakAkun', function ($query) {
                        $query->whereNotNull('kode_sub_anak_akun')
                            ->where('kode_sub_anak_akun', '!=', '');
                    })
                    ->get();

                $existingBarangIds = $this->opname->details()->pluck('barang_id')->toArray();

                foreach ($barangs as $barang) {
                    if (!in_array($barang->id, $existingBarangIds)) {
                        $qtyJurnal = (float) ($barang->stok_buku_besar ?? 0.0);

                        StockOpnameDetail::create([
                            'stock_opname_id' => $this->opname->id,
                            'barang_id'       => $barang->id,
                            'stok_sistem'     => $qtyJurnal,
                            'stok_aktual'     => null,
                            'selisih'         => null,
                        ]);
                    }
                }
            });
        }

        // Muat ulang detail relasi barang terbaru dari database
        $this->opname->load(['details.barang.subAnakAkun']);

        $this->details = $this->opname->details
            ->filter(function ($d) {
                // Hanya tampilkan barang yang terhubung ke Jurnal (sama seperti Stok Matrix)
                return $d->barang && $d->barang->subAnakAkun && !empty($d->barang->subAnakAkun->kode_sub_anak_akun);
            })
            ->map(function ($d) {
                // Jika masih draft, pastikan stok_sistem dinamis mengikuti saldo JurnalUmum (stok_buku_besar) real-time
                $stokSistem = $this->opname->isDraft()
                    ? (float) ($d->barang?->stok_buku_besar ?? 0.0)
                    : (float) $d->stok_sistem;

                return [
                    'id' => $d->id,
                    'barang_id' => $d->barang_id,
                    'barang' => $d->barang->nama_barang ?? '-',
                    'kode' => $d->barang->kode_barang ?? '-',
                    'stok_sistem' => $stokSistem,
                    'stok_aktual' => $d->stok_aktual !== null ? (string) $d->stok_aktual : '',
                    'catatan' => $d->catatan ?? '',
                ];
            })
            ->values()
            ->toArray();
    }

    /* =========================
     |  SIMPAN PROGRESS
     ========================= */

    public function simpanProgress(): void
    {
        if (!$this->opname || !$this->opname->isDraft())
            return;

        DB::transaction(function () {
            foreach ($this->details as $item) {
                StockOpnameDetail::where('id', $item['id'])->update([
                    'stok_sistem' => (float) $item['stok_sistem'], // Simpan stok_sistem terbaru dari jurnal
                    'stok_aktual' => $item['stok_aktual'] !== '' ? (float) $item['stok_aktual'] : null,
                    'catatan' => $item['catatan'] ?: null,
                ]);
            }
        });

        Notification::make()->title('Progress tersimpan')->success()->send();
    }

    /* =========================
     |  SUBMIT APPROVAL
     ========================= */

    public function submitApproval(): void
    {
        if (!$this->opname)
            return;

        $this->simpanProgress();
        $this->opname->refresh()->load('details');

        try {
            app(StockOpnameService::class)->submitUntukApproval($this->opname, auth()->id());

            Notification::make()
                ->title('Opname berhasil disubmit, menunggu approval')
                ->success()
                ->send();

            $this->loadDetails();
            $this->refreshDaftarOpname();
            $this->refreshRiwayatOpname();
        } catch (\Exception $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    /* =========================
     |  APPROVE
     ========================= */

    /* =========================
     |  APPROVE & POST TO JURNAL PEMBANTU
     ========================= */

    public function approve(): void
    {
        if (!$this->opname) {
            return;
        }

        try {
            DB::transaction(function () {
                // 1. Jalankan approval internal status dokumen opname melalui service logistik
                app(StockOpnameService::class)->approve(
                    $this->opname,
                    auth()->id(),
                    $this->catatan_approval ?: null
                );

                // Load detail barang opname beserta relasi akun keuangannya
                $this->opname->load('details.barang.subAnakAkun');

                // Hitung nomor urut jurnal berikutnya (max + 1)
                $nextJurnalNo = (int) (\App\Models\JurnalUmum::max('jurnal') ?? 0) + 1;
                $maxJP = (int) (\App\Models\JurnalPembantuHeader::max('jurnal') ?? 0);
                $nextJurnalNo = max($nextJurnalNo, $maxJP) + 1;

                foreach ($this->opname->details as $detail) {
                    $barang = $detail->barang;
                    $subAkun = $barang?->subAnakAkun;
                    $kodeAkun = $subAkun?->kode_sub_anak_akun;
                    $namaAkun = $subAkun?->nama_sub_anak_akun;

                    // Lewati barang jika belum dikaitkan dengan nomor akun keuangan akuntansi
                    if (!$kodeAkun) {
                        continue;
                    }

                    // 🔍 HITUNG REAL-TIME STOK SEBELUMNYA DARI JURNAL UMUM (LEDGER)
                    $stokBukuBesarTerkini = (float) ($barang->stok_buku_besar ?? 0.0);

                    // 🔍 CARI SELISIH NYATA: FISIK DI INPUT GUDANG VS TOTAL HITUNGAN BUKU BESAR
                    $stokAktualFisik = (float) ($detail->stok_aktual ?? 0);
                    $selisihOpname = $stokAktualFisik - $stokBukuBesarTerkini;

                    // Jika fisik dan komputer sudah sama, lewati (tidak perlu penyesuaian stok)
                    if ($selisihOpname == 0) {
                        continue;
                    }

                    // Tentukan Debet (Barang Masuk/Nambah) atau Kredit (Barang Keluar/Kurang)
                    $mapHeaderType = $selisihOpname > 0 ? 'd' : 'k';
                    $mapItemType = $selisihOpname > 0 ? 'debit' : 'kredit';
                    $qtyPenyesuaian = abs($selisihOpname);

                    // 💡 HITUNG NOMOR URUT INTEGER UNTUK NO JURNAL PEMBANTU
                    $nextNoJurnalPembantu = (int) (\App\Models\JurnalPembantuHeader::max('no_jurnal_pembantu') ?? 0) + 1;

                    // 2. TERBITKAN JURNAL PEMBANTU HEADER UNTUK PRODUK INI (STATUS DRAFT - SELARAS DENGAN YANG LAIN)
                    $jurnalPembantuHeader = \App\Models\JurnalPembantuHeader::create([
                        'no_jurnal_pembantu'  => $nextNoJurnalPembantu,
                        'tgl_transaksi'       => now()->format('Y-m-d'),
                        'jenis_transaksi'     => 'so',
                        'modul_asal'          => 'stock_opname',
                        'jurnal'              => $nextJurnalNo,
                        'no_akun'             => $kodeAkun, // Akun persediaan produk ini!
                        'nama_akun'           => $namaAkun,
                        'map'                 => $mapHeaderType,
                        'no_dokumen'          => $this->opname->no_opname ?? 'OPNAME_STOK',
                        'keterangan'          => 'Opname Penyesuaian Fisik: ' . $barang->nama_barang . ' (Selisih: ' . ($selisihOpname > 0 ? '+' : '') . $selisihOpname . ')',
                        'total_nilai'         => 0.0, // Harga 0, total_nilai 0 agar tidak mempengaruhi balance
                        'status'              => \App\Models\JurnalPembantuHeader::STATUS_DRAFT, // Disimpan sebagai Draft
                        'adalah_jurnal_balik' => false,
                        'dibuat_oleh'         => auth()->id(),
                    ]);

                    // 3. MASUKKAN STOK MELALUI JURNAL PEMBANTU ITEM (TERIKAT HEADER) DENGAN HARGA 0
                    $jurnalPembantuHeader->items()->create([
                        'urut'         => 1,
                        'barang_id'    => $barang->id,
                        'no_akun'      => $kodeAkun,
                        'nama_akun'    => $namaAkun,
                        'map'          => $mapItemType,
                        'nama_barang'  => $barang->nama_barang,
                        'no_dokumen'   => $this->opname->no_opname ?? 'OPNAME_STOK',
                        'banyak'       => $qtyPenyesuaian,
                        'harga'        => 0.0, // Harga 0 agar tidak mempengaruhi balance
                        'jumlah'       => 0.0, // Jumlah 0 agar tidak mempengaruhi balance
                        'status'       => true, // Item aktif
                        'keterangan'   => 'Opname Penyesuaian Fisik: ' . $barang->nama_barang . ' (Selisih: ' . ($selisihOpname > 0 ? '+' : '') . $selisihOpname . ')',
                        'created_by'   => auth()->id(),
                    ]);
                }
            });

            // Sampaikan notifikasi sukses jika transaction database berhasil tanpa rollback
            Notification::make()
                ->title('Opname disetujui, Jurnal Pembantu & Stok Baru sukses tercatat')
                ->success()
                ->send();

            // Bersihkan form halaman kustom Livewire kembali ke kondisi semula
            $this->reset(['opname', 'details', 'catatan_approval', 'toko_id', 'catatan']);
            $this->form->fill();
            $this->refreshDaftarOpname();
            $this->refreshRiwayatOpname();
        } catch (\Exception $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    /* =========================
     |  TOLAK
     ========================= */

    public function tolak(): void
    {
        if (!$this->opname)
            return;

        try {
            app(StockOpnameService::class)->tolak(
                $this->opname,
                auth()->id(),
                $this->catatan_approval ?: null
            );

            Notification::make()->title('Opname ditolak')->warning()->send();

            $this->reset(['opname', 'details', 'catatan_approval', 'toko_id', 'catatan']);
            $this->form->fill();
            $this->refreshDaftarOpname();
            $this->refreshRiwayatOpname();
        } catch (\Exception $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    /* =========================
     |  BATAL
     ========================= */

    public function batal(): void
    {
        $this->reset(['opname', 'details', 'catatan_approval', 'toko_id', 'catatan']);
        $this->form->fill();
        $this->refreshDaftarOpname();
        $this->refreshRiwayatOpname();
    }

    /* =========================
     |  PERMISSION
     ========================= */

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'manager']) ?? false;
    }
}
