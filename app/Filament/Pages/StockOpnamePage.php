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

    public function approve(): void
    {
        if (!$this->opname)
            return;

        try {
            app(StockOpnameService::class)->approve(
                $this->opname,
                auth()->id(),
                $this->catatan_approval ?: null
            );

            Notification::make()
                ->title('Opname disetujui, stok berhasil disesuaikan')
                ->success()
                ->send();

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