<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use App\Models\IdentitasToko;
use App\Models\ProduksiPakan;
use App\Models\ProduksiPakanMentah;
use App\Models\ProduksiPakanCampuran;
use App\Models\StokBarangToko;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Support\Facades\Log;

class ProduksiPakanLaporan extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel  = 'Produksi Pakan';
    protected static ?string $title            = 'Laporan Produksi Pakan';
    protected static ?string $slug             = 'produksi-pakan-laporan';
    protected string $view                     = 'filament.pages.produksi-pakan-laporan';

    /* ─── State ─────────────────────────────────────────────────────────── */

    public ?string        $selectedDate  = null;
    public ?ProduksiPakan $currentRecord = null;

    public array   $mentahState   = [];
    public array   $campuranState = [];
    public ?string $keterangan    = '';
    public bool    $isLocked      = false;

    /* ─── Permission flags (dikompute di computePermissions()) ─────────── */

    /**
     * true  → user punya role super_admin
     */
    public bool $isSuperAdmin = false;

    /**
     * true  → user yang sedang login adalah yang membuat draft ini
     */
    public bool $isCreator = false;

    /**
     * true  → record sudah pernah disimpan ke DB (minimal sekali klik Simpan Draft)
     *         CATATAN: record TIDAK terbuat otomatis saat ganti tanggal —
     *         hanya terbuat saat user klik Simpan Draft.
     */
    public bool $isDraftSaved = false;

    /**
     * Apakah user boleh mengisi / mengubah input di halaman ini.
     *
     * Aturan:
     *   super_admin              → selalu true
     *   isLocked                 → false   (laporan sudah divalidasi)
     *   isDraftSaved && isCreator → false  (creator terkunci setelah save pertama)
     *   else                     → true    (validator bebas edit sampai divalidasi)
     */
    public bool $canEdit = true;

    /**
     * Tombol "Simpan Draft" ditampilkan / tidak.
     */
    public bool $showSaveButton = true;

    /**
     * Tombol "Validasi & Kunci" ditampilkan / tidak.
     *
     * Aturan:
     *   super_admin              → true (kalau belum locked)
     *   isCreator                → false (creator tidak boleh validasi miliknya)
     *   else (validator)         → true (kalau draft sudah ada)
     */
    public bool $showValidateButton = false;

    /* ─── Internal flag, mencegah infinite loop recalculate ─────────────── */
    protected bool $isRecalculating = false;

    /* ═══════════════════════════════════════════════════════════════════════
     |  LIFECYCLE
     ═══════════════════════════════════════════════════════════════════════ */

    public function mount(): void
    {
        // Deteksi super_admin sekali saat mount, disimpan di property publik
        // supaya blade bisa memanfaatkannya lewat $isSuperAdmin jika diperlukan.
        $this->isSuperAdmin = Auth::user()->hasRole('super_admin');

        $this->selectedDate = now()->format('Y-m-d');
        $this->loadDataByDate();
    }

    public function updatedSelectedDate(): void
    {
        $this->loadDataByDate();
    }

    /* ═══════════════════════════════════════════════════════════════════════
     |  LOAD DATA
     ═══════════════════════════════════════════════════════════════════════ */

    public function loadDataByDate(): void
    {
        if (! $this->selectedDate) return;

        $date = Carbon::parse($this->selectedDate)->toDateString();

        // Eager load relasi yang dibutuhkan sekaligus
        $this->currentRecord = ProduksiPakan::with([
            'pakanMentahs.barang.satuan',
            'pakanCampurans.barang.satuan',
        ])->whereDate('tanggal_produksi', $date)->first();

        if (! $this->currentRecord) {
            // ── Tanggal belum punya record ──────────────────────────────────
            // TIDAK langsung buat record ke DB.
            // Kita hanya isi state dari daftar Barang + stok kandang,
            // sehingga user tetap bisa melihat form & mengisinya.
            // Record baru benar-benar dibuat saat user klik "Simpan Draft".
            $this->isDraftSaved = false;
            $this->isLocked     = false;
            $this->isCreator    = false;
            $this->keterangan   = '';

            $this->buildStateFromBarang();   // ← isi mentahState & campuranState
            $this->computePermissions();
            return;
        }

        // ── Record sudah ada ────────────────────────────────────────────────
        $this->isDraftSaved = true;
        $this->isLocked     = ! empty($this->currentRecord->validated_by);
        $this->isCreator    = ($this->currentRecord->created_by === Auth::user()->name);
        $this->keterangan   = $this->currentRecord->keterangan ?? '';

        // Mapping Mentah — nama barang sudah menyertakan satuan: "Jagung (Sak)"
        $this->mentahState = $this->currentRecord->pakanMentahs
            ->map(fn($item) => [
                'id'       => $item->id,
                'barang_id' => $item->id_barang,
                'nama'     => $this->formatNamaSatuan(
                    $item->barang?->nama_barang,
                    $item->barang?->satuan?->nama_satuan
                ),
                'satuan'   => $item->barang?->satuan?->nama_satuan ?? '-',
                'awal'     => (float) $item->stok_awal,
                'p'        => (float) $item->keluar_pullet,
                'l1'       => (float) $item->keluar_l1,
                'l2'       => (float) $item->keluar_l2,
                'akhir'    => (float) $item->stok_akhir,
            ])->toArray();

        // Mapping Campuran
        $this->campuranState = $this->currentRecord->pakanCampurans
            ->map(fn($item) => [
                'id'       => $item->id,
                'barang_id' => $item->id_barang,
                'nama'     => $this->formatNamaSatuan(
                    $item->barang?->nama_barang,
                    $item->barang?->satuan?->nama_satuan
                ),
                'satuan'   => $item->barang?->satuan?->nama_satuan ?? '-',
                'awal'     => (float) $item->stok_awal,
                'masuk'    => (float) $item->masuk,
                'p'        => (float) $item->keluar_pullet,
                'l1'       => (float) $item->keluar_l1,
                'l2'       => (float) $item->keluar_l2,
                'akhir'    => (float) $item->stok_akhir,
            ])->toArray();

        $this->computePermissions();
    }

    /* ─── Helper: format "Nama Barang (Satuan)" ─────────────────────────── */

    private function formatNamaSatuan(?string $nama, ?string $satuan): string
    {
        $nama   = $nama   ?? 'Unknown';
        $satuan = $satuan ?? '-';
        return "{$nama} ({$satuan})";
    }

    /* ─── Bangun state dari Barang + stok kandang (tanpa menyentuh DB) ─── */

    /**
     * Dipanggil ketika tanggal belum punya record.
     * Mengambil semua barang kategori "pakan", lalu mengambil stok dari
     * StokBarangToko milik toko "kandang" sebagai stok_awal.
     *
     * Kenapa tidak langsung create ke DB? Karena kita hanya mau create
     * saat user memang berniat menyimpan (klik Simpan Draft), bukan sekadar
     * mengintip data tanggal lain.
     */
    private function buildStateFromBarang(): void
    {
        // Cari toko "kandang" — case-insensitive
        $kandangToko = IdentitasToko::whereRaw('LOWER(nama_toko) LIKE ?', ['%kandang%'])
            ->first();

        // Ambil semua barang pakan beserta satuannya
        $semuaBarang = Barang::with('satuan')
            ->whereHas(
                'kategori',
                fn($q) =>
                $q->whereRaw('LOWER(nama_kategori) LIKE ?', ['%pakan%'])
            )->get();

        // Build stok map: barang_id → stok, supaya tidak N+1 query
        $stokMap = [];
        if ($kandangToko) {
            StokBarangToko::where('toko_id', $kandangToko->id)
                ->whereIn('barang_id', $semuaBarang->pluck('id'))
                ->get()
                ->each(fn($s) => $stokMap[$s->barang_id] = (float) $s->stok);
        }

        $mentah   = [];
        $campuran = [];

        foreach ($semuaBarang as $b) {
            $namaUpper  = strtoupper($b->nama_barang);
            $isCampuran = str_contains($namaUpper, 'PULET')
                || str_contains($namaUpper, 'PULLET')
                || str_contains($namaUpper, 'LAYER');

            $stok       = $stokMap[$b->id] ?? 0.0;
            $satuanNama = $b->satuan?->nama_satuan ?? '-';

            $base = [
                'id'        => null,          // belum ada ID karena belum disimpan
                'barang_id' => $b->id,
                'nama'      => $this->formatNamaSatuan($b->nama_barang, $satuanNama),
                'satuan'    => $satuanNama,
                'awal'      => $stok,
                'p'         => 0.0,
                'l1'        => 0.0,
                'l2'        => 0.0,
                'akhir'     => $stok,         // akhir = awal saat belum ada pengeluaran
            ];

            if ($isCampuran) {
                $campuran[] = array_merge($base, ['masuk' => 0.0]);
            } else {
                $mentah[] = $base;
            }
        }

        $this->mentahState   = $mentah;
        $this->campuranState = $campuran;

        Log::info('[ProduksiPakan] State berhasil dibangun', [
            'jumlah_mentah'   => count($mentah),
            'jumlah_campuran' => count($campuran),
            'mentah'   => collect($mentah)->map(fn($r) => [
                'nama'  => $r['nama'],
                'awal'  => $r['awal'],
                'akhir' => $r['akhir'],
            ])->toArray(),
            'campuran' => collect($campuran)->map(fn($r) => [
                'nama'  => $r['nama'],
                'awal'  => $r['awal'],
                'masuk' => $r['masuk'],
                'akhir' => $r['akhir'],
            ])->toArray(),
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════════════
     |  PERMISSION COMPUTATION
     ═══════════════════════════════════════════════════════════════════════ */

    /**
     * Menghitung semua flag permission berdasarkan kondisi saat ini.
     * Dipanggil setelah setiap perubahan state yang relevan.
     *
     * Urutan prioritas (waterfall):
     *   1. super_admin   → boleh segalanya
     *   2. isLocked      → semua ditolak
     *   3. creator & draft sudah disimpan → ditolak edit/save/validate
     *   4. sisanya (validator / belum ada draft) → boleh edit & save
     */
    private function computePermissions(): void
    {
        // ── [1] Super Admin: master key ─────────────────────────────────────
        if ($this->isSuperAdmin) {
            $this->canEdit            = true;
            $this->showSaveButton     = true;
            // Super admin boleh validasi selama belum locked & record sudah ada
            $this->showValidateButton = ! $this->isLocked && $this->currentRecord !== null;
            return;
        }

        // ── [2] Laporan sudah divalidasi → semua terkunci ───────────────────
        if ($this->isLocked) {
            $this->canEdit            = false;
            $this->showSaveButton     = false;
            $this->showValidateButton = false;
            return;
        }

        // ── [3] Creator sudah menyimpan draft → terkunci dari edit ──────────
        //    Creator tidak perlu/tidak boleh memvalidasi laporannya sendiri.
        if ($this->isDraftSaved && $this->isCreator) {
            $this->canEdit            = false;
            $this->showSaveButton     = false;
            $this->showValidateButton = false;
            return;
        }

        // ── [4] Kondisi normal: belum ada draft ATAU user adalah validator ──
        //    - Belum ada draft     : siapapun boleh membuat draft pertama
        //    - Validator           : boleh edit & simpan sebelum validasi
        $this->canEdit        = true;
        $this->showSaveButton = true;

        // Tombol validasi hanya muncul jika:
        //   • Draft sudah ada di DB (creator sudah simpan)
        //   • User BUKAN creator laporan ini
        $this->showValidateButton = $this->isDraftSaved && ! $this->isCreator;
    }

    /* ═══════════════════════════════════════════════════════════════════════
     |  RECALCULATE (dipanggil Livewire saat property berubah)
     ═══════════════════════════════════════════════════════════════════════ */

    public function updated($propertyName): void
    {
        // Guard: jangan recalculate jika tidak boleh edit
        if (! $this->canEdit) return;
        if ($this->isRecalculating) return;

        if (str_contains($propertyName, 'State')) {
            $this->isRecalculating = true;
            $this->recalculateAll();
            $this->isRecalculating = false;
        }
    }

    private function recalculateAll(): void
    {
        $totalP  = 0;
        $totalL1 = 0;
        $totalL2 = 0;

        // Hitung sisa mentah & akumulasi total keluar per kategori
        foreach ($this->mentahState as $idx => $item) {
            $totalKeluar = ($item['p'] ?? 0) + ($item['l1'] ?? 0) + ($item['l2'] ?? 0);
            $this->mentahState[$idx]['akhir'] = max(0, ($item['awal'] ?? 0) - $totalKeluar);

            $totalP  += ($item['p']  ?? 0);
            $totalL1 += ($item['l1'] ?? 0);
            $totalL2 += ($item['l2'] ?? 0);
        }

        // Hitung masuk & sisa campuran (masuk = total mentah yang dipakai)
        foreach ($this->campuranState as $idx => $item) {
            $nama  = strtoupper($item['nama'] ?? '');
            $masuk = 0;

            if (str_contains($nama, 'PULET') || str_contains($nama, 'PULLET'))       $masuk = $totalP;
            elseif (str_contains($nama, 'LAYER 1') || str_contains($nama, 'L1'))     $masuk = $totalL1;
            elseif (str_contains($nama, 'LAYER 2') || str_contains($nama, 'L2'))     $masuk = $totalL2;

            $this->campuranState[$idx]['masuk'] = $masuk;

            $totalMasuk  = ($item['awal'] ?? 0) + $masuk;
            $totalKeluar = ($item['p']    ?? 0) + ($item['l1'] ?? 0) + ($item['l2'] ?? 0);
            $this->campuranState[$idx]['akhir'] = max(0, $totalMasuk - $totalKeluar);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
     |  SAVE (Simpan Draft)
     ═══════════════════════════════════════════════════════════════════════ */

    public function save(): void
    {
        // Guard: tolak jika tidak punya akses edit
        if (! $this->canEdit) return;

        $date = Carbon::parse($this->selectedDate)->toDateString();

        DB::transaction(function () use ($date) {

            if (! $this->currentRecord) {
                // ────────────────────────────────────────────────────────────
                // FIRST SAVE: Record belum ada, buat sekarang
                // Inilah satu-satunya tempat record dibuat ke DB.
                // ────────────────────────────────────────────────────────────
                $this->currentRecord = ProduksiPakan::create([
                    'tanggal_produksi' => $date,
                    'created_by'       => Auth::user()->name,
                    'keterangan'       => $this->keterangan,
                ]);

                foreach ($this->mentahState as $idx => $data) {
                    $record = ProduksiPakanMentah::create([
                        'id_produksi_pakan' => $this->currentRecord->id,
                        'id_barang'         => $data['barang_id'],
                        'stok_awal'         => $data['awal'],
                        'keluar_pullet'     => $data['p']    ?? 0,
                        'keluar_l1'         => $data['l1']   ?? 0,
                        'keluar_l2'         => $data['l2']   ?? 0,
                        'stok_akhir'        => $data['akhir'] ?? 0,
                    ]);
                    // Simpan ID yang baru didapat agar update berikutnya bisa pakai WHERE id
                    $this->mentahState[$idx]['id'] = $record->id;
                }

                foreach ($this->campuranState as $idx => $data) {
                    $record = ProduksiPakanCampuran::create([
                        'id_produksi_pakan' => $this->currentRecord->id,
                        'id_barang'         => $data['barang_id'],
                        'stok_awal'         => $data['awal'],
                        'masuk'             => $data['masuk'] ?? 0,
                        'keluar_pullet'     => $data['p']     ?? 0,
                        'keluar_l1'         => $data['l1']    ?? 0,
                        'keluar_l2'         => $data['l2']    ?? 0,
                        'stok_akhir'        => $data['akhir']  ?? 0,
                    ]);
                    $this->campuranState[$idx]['id'] = $record->id;
                }

                // Tandai bahwa draft sudah tersimpan & user ini adalah creator
                $this->isDraftSaved = true;
                $this->isCreator    = true;
            } else {
                // ────────────────────────────────────────────────────────────
                // UPDATE: Record sudah ada, update baris yang berubah
                // ────────────────────────────────────────────────────────────
                foreach ($this->mentahState as $data) {
                    if (! $data['id']) continue; // jaga-jaga jika ada baris tanpa ID
                    ProduksiPakanMentah::where('id', $data['id'])->update([
                        'keluar_pullet' => $data['p']    ?? 0,
                        'keluar_l1'     => $data['l1']   ?? 0,
                        'keluar_l2'     => $data['l2']   ?? 0,
                        'stok_akhir'    => $data['akhir'] ?? 0,
                    ]);
                }

                foreach ($this->campuranState as $data) {
                    if (! $data['id']) continue;
                    ProduksiPakanCampuran::where('id', $data['id'])->update([
                        'masuk'         => $data['masuk'] ?? 0,
                        'keluar_pullet' => $data['p']     ?? 0,
                        'keluar_l1'     => $data['l1']    ?? 0,
                        'keluar_l2'     => $data['l2']    ?? 0,
                        'stok_akhir'    => $data['akhir']  ?? 0,
                    ]);
                }

                $this->currentRecord->update(['keterangan' => $this->keterangan]);
            }
        });

        // Recompute permission setelah state berubah
        $this->computePermissions();

        Notification::make()->title('Data Disimpan')->success()->send();
    }

    /* ═══════════════════════════════════════════════════════════════════════
     |  VALIDATE
     ═══════════════════════════════════════════════════════════════════════ */

    public function validateData(): void
    {
        // Guard: hanya lanjut jika tombol memang ditampilkan
        if (! $this->showValidateButton) return;

        // Simpan dulu sebelum mengunci
        $this->save();

        $this->currentRecord->update([
            'validated_by' => Auth::user()->name,
            'validated_at' => now(),
        ]);

        $this->isLocked = true;
        $this->computePermissions();

        Notification::make()->title('Laporan Divalidasi')->success()->send();
    }
}
