<?php

namespace App\Filament\Pages;

use App\Models\IdentitasToko;
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

            // Load semua barang yang punya stok di toko ini
            $stoks = StokBarangToko::with('barang')
                ->where('toko_id', $tokoId)
                ->get();

            foreach ($stoks as $stok) {
                StockOpnameDetail::create([
                    'stock_opname_id' => $this->opname->id,
                    'barang_id' => $stok->barang_id,
                    'stok_sistem' => $stok->stok,
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

        $this->opname->load('details.barang');

        $this->details = $this->opname->details
            ->map(fn($d) => [
                'id' => $d->id,
                'barang_id' => $d->barang_id,
                'barang' => $d->barang->nama_barang ?? '-',
                'kode' => $d->barang->kode_barang ?? '-',
                'stok_sistem' => $d->stok_sistem,
                'stok_aktual' => $d->stok_aktual !== null ? (string) $d->stok_aktual : '',
                'catatan' => $d->catatan ?? '',
            ])
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
                // 1. Jalankan approval internal status dokumen opname melalui service logistik Anda
                app(StockOpnameService::class)->approve(
                    $this->opname,
                    auth()->id(),
                    $this->catatan_approval ?: null
                );

                // Load detail barang opname beserta relasi akun keuangannya
                $this->opname->load('details.barang.subAnakAkun');

                // Hitung nomor urut jurnal berikutnya (max + 1)
                // Hitung nomor urut jurnal berikutnya (max + 1)
                $nextJurnalNo = (int) (\App\Models\JurnalPembantuHeader::max('jurnal') ?? 0) + 1;

                // 💡 HITUNG JUGA NOMOR URUT INTEGER UNTUK NO JURNAL PEMBANTU
                // Karena tipenya di database adalah Integer, kita ambil nilai tertinggi lalu tambah 1
                $nextNoJurnalPembantu = (int) (\App\Models\JurnalPembantuHeader::max('no_jurnal_pembantu') ?? 0) + 1;

                // 2. TERBITKAN JURNAL PEMBANTU HEADER
                $jurnalPembantuHeader = \App\Models\JurnalPembantuHeader::create([
                    'no_jurnal_pembantu'  => $nextNoJurnalPembantu,
                    'tgl_transaksi'       => now()->format('Y-m-d'),
                    'jenis_transaksi'     => 'so',
                    'modul_asal'          => 'stock_opname',
                    'jurnal'              => $nextJurnalNo,
                    'no_akun'             => '-',
                    'nama_akun'           => 'Multi Akun Persediaan (Stock Opname)',
                    'map'                 => 'd', // Default formal (Debet)
                    'no_dokumen'          => $this->opname->no_opname ?? 'OPNAME_STOK',
                    'keterangan'          => 'Stock Opname: ' . ($this->opname->catatan ?? 'Penyesuaian Fisik Gudang'),
                    'status'              => \App\Models\JurnalPembantuHeader::STATUS_DIPOSTING,
                    'adalah_jurnal_balik' => false,
                    'dibuat_oleh'         => auth()->id(),
                    'diposting_oleh'      => auth()->id(),
                    'tgl_posting'         => now(),
                ]);
                // Variabel bantu untuk indexing nomor urut item di dalam loop
                $loopIndex = 0;

                foreach ($this->opname->details as $detail) {
                    $barang = $detail->barang;
                    $subAkun = $barang?->subAnakAkun;
                    $kodeAkun = $subAkun?->kode_sub_anak_akun;
                    $namaAkun = $subAkun?->nama_sub_anak_akun;

                    // Lewati barang jika belum dikaitkan dengan nomor akun keuangan akuntansi
                    if (!$kodeAkun) {
                        continue;
                    }

                    // 🔍 HITUNG REAL-TIME STOK SEBELUMNYA DARI JURNAL PEMBANTU ITEM
                    // Menghitung timbunan saldo berjalan (running balance) khusus untuk ID barang ini
                    $transaksis = \App\Models\JurnalPembantuItem::where('barang_id', $barang->id)
                        ->whereHas('header', function ($query) use ($kodeAkun) {
                            $query->where('no_akun', $kodeAkun)->where('status', '!=', \App\Models\JurnalPembantuHeader::STATUS_DRAFT);
                        })
                        ->get();

                    $stokBukuBesarTerkini = 0.0;

                    foreach ($transaksis as $trx) {
                        // Cari peta posisi (map) apakah dari item atau fallback ke header
                        $isDebit = in_array(strtolower($trx->map ?? $trx->header?->map), ['d', 'debit']);
                        $qtyTrx = (float) ($trx->banyak ?? 0);

                        if ($isDebit) {
                            $stokBukuBesarTerkini += $qtyTrx;
                        } else {
                            $stokBukuBesarTerkini -= $qtyTrx;
                        }
                    }

                    // 🔍 CARI SELISIH NYATA: FISIK DI INPUT GUDANG VS TOTAL HITUNGAN BUKU BESAR
                    $stokAktualFisik = (float) ($detail->stok_aktual ?? 0);
                    $selisihOpname = $stokAktualFisik - $stokBukuBesarTerkini;

                    // Jika fisik dan komputer sudah sama, lewati (tidak perlu penyesuaian stok)
                    if ($selisihOpname == 0) {
                        continue;
                    }

                    // Tentukan Debet (Barang Masuk/Nambah) atau Kredit (Barang Keluar/Kurang)
                    $mapType = $selisihOpname > 0 ? 'debit' : 'kredit';
                    $qtyPenyesuaian = abs($selisihOpname);

                    // 3. MASUKKAN STOK MELALUI JURNAL PEMBANTU ITEM (TERIKAT HEADER)
                    // Logika booting model Anda akan otomatis menghitung perkalian kolom 'jumlah'
                    $jurnalPembantuHeader->items()->create([
                        'urut'         => ++$loopIndex,
                        'barang_id'    => $barang->id, // Kolom identitas baru logistik
                        'no_akun'      => $kodeAkun,   // Kolom identitas baru logistik
                        'nama_akun'    => $namaAkun,   // Kolom identitas baru logistik
                        'map'          => $mapType,    // Mengunci arah pergerakan stok 'debit' / 'kredit'
                        'nama_barang'  => $barang->nama_barang,
                        'no_dokumen'   => $this->opname->no_opname ?? 'OPNAME_STOK',
                        'banyak'       => $qtyPenyesuaian, // Disimpan presisi decimal:4 sesuai timbangan pakan Anda
                        'harga'        => (float) ($barang->harga_pokok ?? 0),
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
