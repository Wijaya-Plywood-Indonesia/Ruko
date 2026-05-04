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
use Illuminate\Support\Facades\Session; // ← TAMBAHAN: untuk simpan draft sementara
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

    /* ─── Permission flags ──────────────────────────────────────────────── */
    public bool $isSuperAdmin      = false;
    public bool $isCreator         = false;
    public bool $isDraftSaved      = false;
    public bool $canEdit           = true;
    public bool $showSaveButton    = true;
    public bool $showValidateButton = false;

    /* ─── Internal flag ─────────────────────────────────────────────────── */
    protected bool $isRecalculating = false;

    /* ═══════════════════════════════════════════════════════════════════════
     |  SESSION HELPERS
     |
     |  Bayangkan Session seperti "catatan sticky" yang ditempel di server
     |  per-user per-tanggal. Isinya hanya nilai input yang belum disimpan ke DB.
     |  Key format: pp_draft_{userId}_{tanggal}
     ═══════════════════════════════════════════════════════════════════════ */

    /**
     * Membuat key unik untuk session draft ini.
     * Kenapa pakai userId? Supaya draft user A tidak bocor ke user B
     * meskipun mereka membuka halaman yang sama.
     */
    private function sessionKey(?string $date = null): string
    {
        $date = $date ?? $this->selectedDate;
        return 'pp_draft_' . Auth::id() . '_' . $date;
    }

    /**
     * Simpan hanya nilai-nilai yang bisa diinput user ke Session.
     * Kita TIDAK menyimpan 'awal', 'akhir', 'masuk' — itu dihitung ulang.
     * Cukup simpan 'p', 'l1', 'l2' yang diketik user, diindex by barang_id
     * agar bisa dipasangkan kembali meski urutan array berubah.
     */
    private function saveToSession(): void
    {
        // Tidak ada gunanya simpan ke session jika user tidak boleh edit
        if (! $this->canEdit || ! $this->selectedDate) return;

        Session::put($this->sessionKey(), [
            'mentah' => collect($this->mentahState)
                ->keyBy('barang_id')
                ->map(fn($i) => [
                    'p'  => $i['p']  ?? 0,
                    'l1' => $i['l1'] ?? 0,
                    'l2' => $i['l2'] ?? 0,
                ])
                ->toArray(),

            'campuran' => collect($this->campuranState)
                ->keyBy('barang_id')
                ->map(fn($i) => [
                    'p'  => $i['p']  ?? 0,
                    'l1' => $i['l1'] ?? 0,
                    'l2' => $i['l2'] ?? 0, // ← tambah
                ])
                ->toArray(),

            'keterangan' => $this->keterangan,
            'saved_at'   => now()->toIso8601String(),
        ]);
    }

    /**
     * Tempel nilai dari Session ke state yang sudah ada.
     * Kenapa "tempel" (overlay), bukan "ganti penuh"?
     * Karena state sudah punya data stok_awal dari DB/barang.
     * Kita hanya perlu mengembalikan nilai input yang belum tersimpan.
     *
     * @return bool true jika ada session draft yang berhasil dipulihkan
     */
    private function restoreFromSession(): bool
    {
        $draft = Session::get($this->sessionKey());

        // Tidak ada session draft → tidak perlu restore
        if (! $draft) return false;

        // Tempel nilai mentah
        $mentahDraft = $draft['mentah'] ?? [];
        foreach ($this->mentahState as $idx => $item) {
            $saved = $mentahDraft[$item['barang_id']] ?? null;
            if ($saved) {
                $this->mentahState[$idx]['p']  = $saved['p']  ?? 0;
                $this->mentahState[$idx]['l1'] = $saved['l1'] ?? 0;
                $this->mentahState[$idx]['l2'] = $saved['l2'] ?? 0;
            }
        }

        // Tempel nilai campuran
        $campuranDraft = $draft['campuran'] ?? [];
        foreach ($this->campuranState as $idx => $item) {
            $saved = $campuranDraft[$item['barang_id']] ?? null;
            if ($saved) {
                $this->campuranState[$idx]['p']  = $saved['p']  ?? 0;
                $this->campuranState[$idx]['l1'] = $saved['l1'] ?? 0;
            }
        }

        $this->keterangan = $draft['keterangan'] ?? '';

        // Hitung ulang 'akhir' & 'masuk' berdasarkan nilai yang baru ditempel
        $this->recalculateAll();

        Log::info('[ProduksiPakan] Session draft dipulihkan', [
            'user_id' => Auth::id(),
            'date'    => $this->selectedDate,
            'saved_at' => $draft['saved_at'] ?? null,
        ]);

        return true;
    }

    /**
     * Hapus session draft setelah berhasil disimpan ke DB.
     * Sudah tidak diperlukan lagi — DB adalah sumber kebenaran sekarang.
     */
    private function clearSession(): void
    {
        Session::forget($this->sessionKey());
    }

    /* ═══════════════════════════════════════════════════════════════════════
     |  LIFECYCLE
     ═══════════════════════════════════════════════════════════════════════ */

    public function mount(): void
    {
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

        $this->currentRecord = ProduksiPakan::with([
            'pakanMentahs.barang.satuan',
            'pakanCampurans.barang.satuan',
        ])->whereDate('tanggal_produksi', $date)->first();

        if (! $this->currentRecord) {
            $this->isDraftSaved = false;
            $this->isLocked     = false;
            $this->isCreator    = false;
            $this->keterangan   = '';

            // 1. Bangun state kosong dari daftar Barang + stok kandang
            $this->buildStateFromBarang();

            // 2. Coba pulihkan nilai input dari session jika ada
            //    Ini yang mencegah data hilang saat refresh
            $this->restoreFromSession();

            $this->computePermissions();
            return;
        }

        // ── Record sudah ada ────────────────────────────────────────────────
        $this->isDraftSaved = true;
        $this->isLocked     = ! empty($this->currentRecord->validated_by);
        $this->isCreator    = ($this->currentRecord->created_by === Auth::user()->name);
        $this->keterangan   = $this->currentRecord->keterangan ?? '';

        $this->mentahState = $this->currentRecord->pakanMentahs
            ->map(fn($item) => [
                'id'        => $item->id,
                'barang_id' => $item->id_barang,
                'nama'      => $this->formatNamaSatuan(
                    $item->barang?->nama_barang,
                    $item->barang?->satuan?->nama_satuan
                ),
                'satuan'    => $item->barang?->satuan?->nama_satuan ?? '-',
                'awal'      => (float) $item->stok_awal,
                'p'         => (float) $item->keluar_pullet,
                'l1'        => (float) $item->keluar_l1,
                'l2'        => (float) $item->keluar_l2,
                'akhir'     => (float) $item->stok_akhir,
            ])->toArray();

        $this->campuranState = $this->currentRecord->pakanCampurans
            ->map(fn($item) => [
                'id'        => $item->id,
                'barang_id' => $item->id_barang,
                'nama'      => $this->formatNamaSatuan(
                    $item->barang?->nama_barang,
                    $item->barang?->satuan?->nama_satuan
                ),
                'satuan'    => $item->barang?->satuan?->nama_satuan ?? '-',
                'awal'      => (float) $item->stok_awal,
                'masuk'     => (float) $item->masuk,
                'p'         => (float) $item->keluar_pullet,
                'l1'        => (float) $item->keluar_l1,
                'l2'        => (float) $item->keluar_l2,
                'akhir'     => (float) $item->stok_akhir,
            ])->toArray();

        // Jika ada perubahan belum tersimpan (session draft) di atas data DB,
        // tempel juga — berguna jika user edit lalu refresh sebelum klik Simpan
        if ($this->canEdit) {
            $this->restoreFromSession();
        }

        $this->computePermissions();
    }

    /* ─── Helper: format "Nama Barang (Satuan)" ─────────────────────────── */

    private function formatNamaSatuan(?string $nama, ?string $satuan): string
    {
        return ($nama ?? 'Unknown') . ' (' . ($satuan ?? '-') . ')';
    }

    /* ─── Bangun state dari Barang + stok kandang ─────────────────────── */

    /**
     * LOGIKA STOK AWAL:
     * Kita ambil stok dari StokBarangToko dimana toko-nya adalah "kandang".
     * Kenapa kandang? Karena produksi pakan berada di area kandang,
     * bukan di toko retail. Jadi stok yang relevan adalah stok kandang.
     *
     * Flow:
     *   1. Cari IdentitasToko yang nama_tokonya mengandung kata "kandang"
     *   2. Ambil semua StokBarangToko milik toko tersebut
     *   3. Buat map [barang_id => stok] agar tidak N+1 query saat loop
     *   4. Pasangkan ke setiap Barang yang kategorinya "pakan"
     */
    private function buildStateFromBarang(): void
    {
        // ── DIAGNOSTIC LOG #1: Cari toko kandang ──────────────────────────
        $kandangToko = IdentitasToko::whereRaw('LOWER(nama_toko) LIKE ?', ['%kandang%'])
            ->first();

        Log::info('[PPakan-DEBUG] Step 1: Cari toko kandang', [
            'ditemukan'    => $kandangToko ? 'YA' : 'TIDAK',
            'toko_id'      => $kandangToko?->id,
            'nama_toko'    => $kandangToko?->nama_toko,
            // Tampilkan SEMUA toko agar kita tahu nama aslinya
            'semua_toko'   => IdentitasToko::select('id', 'nama_toko')->get()->toArray(),
        ]);

        // ── DIAGNOSTIC LOG #2: Ambil semua barang pakan ───────────────────
        $semuaBarang = Barang::with('satuan')
            ->whereHas(
                'kategori',
                fn($q) =>
                $q->whereRaw('LOWER(nama_kategori) LIKE ?', ['%pakan%'])
            )->get();

        Log::info('[PPakan-DEBUG] Step 2: Barang pakan ditemukan', [
            'jumlah'  => $semuaBarang->count(),
            'barang'  => $semuaBarang->map(fn($b) => [
                'id'         => $b->id,
                'nama_barang' => $b->nama_barang,
                'kategori'   => $b->kategori?->nama_kategori ?? 'NULL',
            ])->toArray(),
            // Tampilkan semua kategori yang ada di DB untuk crosscheck
            'semua_kategori' => \App\Models\Barang::with('kategori')
                ->get()
                ->pluck('kategori.nama_kategori')
                ->unique()
                ->values()
                ->toArray(),
        ]);

        // ── DIAGNOSTIC LOG #3: Ambil stok dari StokBarangToko ─────────────
        $stokMap = [];
        $rawStokRecords = [];

        if ($kandangToko) {
            $stokMap = StokBarangToko::where('toko_id', $kandangToko->id)
                ->whereIn('barang_id', $semuaBarang->pluck('id'))
                ->get()
                ->mapWithKeys(fn($s) => [$s->barang_id => (float) $s->stok])
                ->toArray();
        }

        Log::info('[PPakan-DEBUG] Step 3: StokBarangToko', [
            'kandang_toko_id'    => $kandangToko?->id,
            'jumlah_stok_record' => count($rawStokRecords),
            'stok_records'       => $rawStokRecords,
            'stok_map'           => $stokMap,
            // Jika kandangToko ada tapi stok kosong, tampilkan SEMUA stok
            // milik toko ini tanpa filter barang_id
            'semua_stok_kandang' => $kandangToko
                ? StokBarangToko::where('toko_id', $kandangToko->id)
                ->get()
                ->map(fn($s) => [
                    'barang_id' => $s->barang_id,
                    'stok'      => $s->stok,
                ])->toArray()
                : [],
        ]);

        // ── Build state (sama seperti sebelumnya) ─────────────────────────
        $mentah   = [];
        $campuran = [];

        foreach ($semuaBarang as $b) {
            $namaUpper  = strtoupper($b->nama_barang);
            $isCampuran = str_contains($namaUpper, 'PULET')
                || str_contains($namaUpper, 'PULLET')
                || str_contains($namaUpper, 'LAYER');

            $stok       = $stokMap[$b->id] ?? 0.0;
            $satuanNama = $b->satuan?->nama_satuan ?? '-';

            Log::info('[PPakan-DEBUG] Step 4: Mapping barang', [
                'barang_id'   => $b->id,
                'nama'        => $b->nama_barang,
                'isCampuran'  => $isCampuran,
                'stok_di_map' => $stokMap[$b->id] ?? 'TIDAK ADA DI MAP → default 0',
            ]);

            $base = [
                'id'        => null,
                'barang_id' => $b->id,
                'nama'      => $this->formatNamaSatuan($b->nama_barang, $satuanNama),
                'satuan'    => $satuanNama,
                'awal'      => $stok,
                'p'         => 0.0,
                'l1'        => 0.0,
                'l2'        => 0.0,
                'akhir'     => $stok,
            ];

            if ($isCampuran) {
                $campuran[] = array_merge($base, ['masuk' => 0.0]);
            } else {
                $mentah[] = $base;
            }
        }

        $this->mentahState   = $mentah;
        $this->campuranState = $campuran;
    }

    /* ═══════════════════════════════════════════════════════════════════════
     |  PERMISSION COMPUTATION
     ═══════════════════════════════════════════════════════════════════════ */

    private function computePermissions(): void
    {
        if ($this->isSuperAdmin) {
            $this->canEdit            = true;
            $this->showSaveButton     = true;
            $this->showValidateButton = ! $this->isLocked && $this->currentRecord !== null;
            return;
        }

        if ($this->isLocked) {
            $this->canEdit            = false;
            $this->showSaveButton     = false;
            $this->showValidateButton = false;
            return;
        }

        if ($this->isDraftSaved && $this->isCreator) {
            $this->canEdit            = false;
            $this->showSaveButton     = false;
            $this->showValidateButton = false;
            return;
        }

        $this->canEdit            = true;
        $this->showSaveButton     = true;
        $this->showValidateButton = $this->isDraftSaved && ! $this->isCreator;
    }

    /* ═══════════════════════════════════════════════════════════════════════
     |  RECALCULATE
     ═══════════════════════════════════════════════════════════════════════ */

    public function updated($propertyName): void
    {
        if (! $this->canEdit) return;
        if ($this->isRecalculating) return;

        if (str_contains($propertyName, 'State') || $propertyName === 'keterangan') {
            $this->isRecalculating = true;
            $this->recalculateAll();
            $this->isRecalculating = false;

            // Simpan ke session setiap kali ada perubahan input
            // Ini yang memastikan data tidak hilang saat refresh
            $this->saveToSession();
        }
    }

    private function recalculateAll(): void
    {
        $totalP  = 0.0;
        $totalL1 = 0.0;
        $totalL2 = 0.0;

        foreach ($this->mentahState as $idx => $item) {
            $p  = (float) ($item['p']  ?? 0);
            $l1 = (float) ($item['l1'] ?? 0);
            $l2 = (float) ($item['l2'] ?? 0);

            $totalKeluar = $p + $l1 + $l2;
            $this->mentahState[$idx]['akhir'] = max(0, (float) ($item['awal'] ?? 0) - $totalKeluar);

            // Simpan kembali sebagai float agar operasi berikutnya aman
            $this->mentahState[$idx]['p']  = $p;
            $this->mentahState[$idx]['l1'] = $l1;
            $this->mentahState[$idx]['l2'] = $l2;

            $totalP  += $p;
            $totalL1 += $l1;
            $totalL2 += $l2;
        }

        foreach ($this->campuranState as $idx => $item) {
            $nama  = strtoupper($item['nama'] ?? '');
            $masuk = 0.0;

            if (str_contains($nama, 'PULET') || str_contains($nama, 'PULLET'))   $masuk = $totalP;
            elseif (str_contains($nama, 'LAYER 1') || str_contains($nama, 'L1')) $masuk = $totalL1;
            elseif (str_contains($nama, 'LAYER 2') || str_contains($nama, 'L2')) $masuk = $totalL2;

            $p  = (float) ($item['p']  ?? 0);
            $l1 = (float) ($item['l1'] ?? 0);
            $l2 = (float) ($item['l2'] ?? 0); // ← tambah


            $this->campuranState[$idx]['masuk'] = $masuk;
            $this->campuranState[$idx]['p']     = $p;
            $this->campuranState[$idx]['l1']    = $l1;
            $this->campuranState[$idx]['l2']    = $l2; // ← tambah

            $totalMasuk  = (float) ($item['awal'] ?? 0) + $masuk;
            $totalKeluar = $p + $l1 + $l2;
            $this->campuranState[$idx]['akhir'] = max(0, $totalMasuk - $totalKeluar);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
     |  SAVE
     ═══════════════════════════════════════════════════════════════════════ */

    public function save(): void
    {
        if (! $this->canEdit) return;

        $date = Carbon::parse($this->selectedDate)->toDateString();

        DB::transaction(function () use ($date) {

            if (! $this->currentRecord) {
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
                        'keluar_pullet'     => $data['p']     ?? 0,
                        'keluar_l1'         => $data['l1']    ?? 0,
                        'keluar_l2'         => $data['l2']    ?? 0,
                        'stok_akhir'        => $data['akhir'] ?? 0,
                    ]);
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
                        'stok_akhir'        => $data['akhir'] ?? 0,
                    ]);
                    $this->campuranState[$idx]['id'] = $record->id;
                }

                $this->isDraftSaved = true;
                $this->isCreator    = true;
            } else {
                foreach ($this->mentahState as $data) {
                    if (! $data['id']) continue;
                    ProduksiPakanMentah::where('id', $data['id'])->update([
                        'keluar_pullet' => $data['p']     ?? 0,
                        'keluar_l1'     => $data['l1']    ?? 0,
                        'keluar_l2'     => $data['l2']    ?? 0,
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
                        'stok_akhir'    => $data['akhir'] ?? 0,
                    ]);
                }

                $this->currentRecord->update(['keterangan' => $this->keterangan]);
            }
        });

        // Data sudah aman di DB → hapus session draft, tidak diperlukan lagi
        $this->clearSession();

        $this->computePermissions();
        Notification::make()->title('Data Disimpan')->success()->send();
    }

    /* ═══════════════════════════════════════════════════════════════════════
     |  VALIDATE
     ═══════════════════════════════════════════════════════════════════════ */

    public function validateData(): void
    {
        if (! $this->showValidateButton) return;

        $this->save(); // save() sudah otomatis clearSession() di dalamnya

        $this->currentRecord->update([
            'validated_by' => Auth::user()->name,
            'validated_at' => now(),
        ]);

        $this->isLocked = true;
        $this->computePermissions();

        Notification::make()->title('Laporan Divalidasi')->success()->send();
    }
}
