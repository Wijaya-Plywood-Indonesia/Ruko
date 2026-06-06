<?php

namespace App\Filament\Pages;

use App\Models\DetailProduksiTelur;
use App\Models\Kandang;
use App\Models\ProduksiPakanCampuran;
use App\Models\ProduksiTelur;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use UnitEnum;

class ProduksiTelurPage extends Page
{
    protected static ?string $navigationLabel = 'Produksi Telur';
    protected static ?string $title = 'Produksi Telur';
    protected static UnitEnum|string|null $navigationGroup = 'Produksi & Kandang';
    protected static ?int $navigationSort = 4;

    public function getView(): string
    {
        return 'filament.pages.produksi-telur-page';
    }

    // ─── State Utama (Matriks Excel) ─────────────────────────

    public string $tanggal = '';
    protected string $tanggalSebelumnya = '';
    public bool $is_validated = false;
    public bool $isEditable = true;
    public ?int $produksiTelurId = null;

    public string $namaUserLogin    = '';  // nama user yang sedang login
    public string $namaPenyimpan    = '';  // nama user yang menyimpan data ini
    public string $namaValidator    = '';  // nama user yang memvalidasi
    public string $waktuValidasi    = '';  // waktu validasi dalam format human-readable

    /**
     * Grid data 10 baris input per kandang:
     * $gridData[id_kandang][rowIndex] = ['id' => x, 'butir' => x, 'kilo' => y, 'tray' => z]
     */
    public array $gridData = [];

    /**
     * Dropdown pilihan pakan campuran aktif per kandang:
     * $kandangPakan[id_kandang] = id_produksi_pakan_campuran
     */
    public array $kandangPakan = [];

    /**
     * Akumulasi total per kandang untuk footer:
     * $kandangTotals[id_kandang] = ['butir' => x, 'kilo' => y, 'tray' => z]
     */
    public array $kandangTotals = [];

    /**
     * Akumulasi total keseluruhan untuk header summary:
     */
    public array $grandTotal = ['butir' => 0, 'kilo' => 0.00, 'tray' => 0];

    // ─── Status & Izin Pengeditan Sesuai Produksi Pakan ───

    public bool $isSuperAdmin = false;
    public bool $isCreator = false;
    public bool $isDraftSaved = false;
    public bool $isLocked = false;
    public bool $canEdit = true;
    public bool $showSaveButton = true;
    public bool $showValidateButton = false;

    // ─── Supporting Data ─────────────────────────────────────

    public array $kandangs = [];
    public array $allPakan = [];
    public int $maxRows = 10; // 10 baris input harian kaku sesuai template Excel Anda

    // ─── Lifecycle Hooks ─────────────────────────────────────

    public function mount(): void
    {
        $this->isSuperAdmin = Auth::user()->hasRole('super_admin');
        $this->tanggal = now()->toDateString();
        $this->tanggalSebelumnya = $this->tanggal;
        $this->loadKandangs();
        $this->loadPakanByTanggal();
        $this->loadExistingDataByTanggal();
    }

    private function sessionKey(): string
    {
        // Selalu gunakan $this->tanggal (tanggal aktif saat ini)
        // Method ini dipakai untuk READ dan WRITE session tanggal aktif
        return 'pt_draft_' . Auth::id() . '_' . ($this->tanggal ?? 'nodate');
    }

    private function saveToSession(): void
    {
        // Kita simpan gridData dan kandangPakan ke session
        // agar saat refresh, data tidak hilang
        Session::put($this->sessionKey(), [
            'gridData'    => $this->gridData,
            'kandangPakan' => $this->kandangPakan,
        ]);
    }

    private function restoreFromSession(): void
    {
        $draft = Session::get($this->sessionKey());
        if (!$draft) return;

        // Loop kandang yang ada, restore nilai dari session
        // Hanya restore jika kandang tersebut memang ada di session
        foreach ($this->kandangs as $kandang) {
            $id = $kandang['id'];

            // Restore grid data per baris per kandang
            if (isset($draft['gridData'][$id])) {
                foreach ($draft['gridData'][$id] as $rowIdx => $row) {
                    // Jaga struktur: pastikan key id tetap null (belum di-DB)
                    $this->gridData[$id][$rowIdx] = [
                        'id'    => null,
                        'butir' => (int)   ($row['butir'] ?? 0),
                        'kilo'  => (float) ($row['kilo']  ?? 0),
                        'tray'  => (float) ($row['tray']  ?? 0),
                    ];
                }
            }

            // Restore pilihan pakan per kandang
            if (isset($draft['kandangPakan'][$id])) {
                $this->kandangPakan[$id] = $draft['kandangPakan'][$id];
            }
        }
    }

    protected function loadKandangs(): void
    {
        $this->kandangs = Kandang::orderBy('nama_kandang')->get(['id', 'nama_kandang'])->toArray();
        $this->resetMatrix();
    }

    protected function loadPakanByTanggal(): void
    {
        $this->allPakan = ProduksiPakanCampuran::query()
            ->join('produksi_pakans', 'produksi_pakan_campurans.id_produksi_pakan', '=', 'produksi_pakans.id')
            ->join('barangs', 'produksi_pakan_campurans.id_barang', '=', 'barangs.id')
            ->whereDate('produksi_pakans.tanggal_produksi', $this->tanggal)
            ->where(function ($query) {
                $query->where('produksi_pakan_campurans.keluar_pullet', '>', 0)
                    ->orWhere('produksi_pakan_campurans.keluar_l1', '>', 0)
                    ->orWhere('produksi_pakan_campurans.keluar_l2', '>', 0);
            })
            ->orderBy('produksi_pakan_campurans.id')
            ->get([
                'produksi_pakan_campurans.id',
                'barangs.nama_barang',
            ])
            ->toArray();
    }

    public function loadExistingDataByTanggal(): void
    {
        // 1. Dapatkan record produksi induk tanpa menggunakan eager loading relasi 'details' yang bermasalah
        $produksi = ProduksiTelur::whereDate('tanggal', $this->tanggal)->first();

        if (! $produksi) {
            $this->resetMatrix();

            // ✅ TAMBAHAN: Jika DB kosong, coba restore dari session
            // Ini yang membuat data tidak hilang saat refresh
            $this->restoreFromSession();

            $this->recalculate();
            return;
        }

        $this->produksiTelurId = $produksi->id;
        $this->is_validated    = (bool) $produksi->is_validated;

        // Evaluasi perizinan dan peran
        $this->computePermissions($produksi);

        // 2. Ambil detail harian langsung memanfaatkan model DetailProduksiTelur secara mandiri
        $details = DetailProduksiTelur::where('id_produksi_telur', $produksi->id)->get();

        // 3. Bangun ulang data matriks 10 baris per kandang
        foreach ($this->kandangs as $kandang) {
            $idKandang = $kandang['id'];
            $this->gridData[$idKandang] = [];
            $this->kandangPakan[$idKandang] = null;

            // Ambil detail yang relevan dengan kandang saat ini
            $kandangDetails = $details->where('id_kandang', $idKandang)->values();

            // Setel dropdown pakan aktif kandang berdasarkan entri pakan pertama yang terdeteksi
            if ($kandangDetails->count() > 0) {
                $this->kandangPakan[$idKandang] = $kandangDetails->first()->id_produksi_pakan_campuran;
            }

            for ($i = 0; $i < $this->maxRows; $i++) {
                if (isset($kandangDetails[$i])) {
                    $this->gridData[$idKandang][$i] = [
                        'id' => $kandangDetails[$i]->id,
                        'butir' => $kandangDetails[$i]->jumlah_telur_butir ?? 0,
                        'kilo' => $kandangDetails[$i]->jumlah_telur_kilo ?? 0,
                        'tray' => $kandangDetails[$i]->jumlah_telur_tray ?? 0,
                    ];
                } else {
                    $this->gridData[$idKandang][$i] = [
                        'id' => null,
                        'butir' => 0,
                        'kilo' => 0,
                        'tray' => 0,
                    ];
                }
            }
        }

        $this->recalculate();
    }

    protected function resetMatrix(): void
    {
        foreach ($this->kandangs as $kandang) {
            $id = $kandang['id'];
            $this->gridData[$id] = [];
            for ($i = 0; $i < $this->maxRows; $i++) {
                $this->gridData[$id][$i] = [
                    'id' => null,
                    'butir' => 0,
                    'kilo' => 0,
                    'tray' => 0,
                ];
            }
            $this->kandangPakan[$id] = null;
        }

        $this->produksiTelurId = null;
        $this->is_validated    = false;
        $this->isEditable      = true;
        $this->kandangTotals   = [];
        $this->grandTotal      = ['butir' => 0, 'kilo' => 0.00, 'tray' => 0];

        $this->computePermissions(null);
    }

    // ─── Real-Time Listeners & Recalculate ───────────────────

    public function updatedTanggal(): void
    {
        $keyLama = 'pt_draft_' . Auth::id() . '_' . $this->tanggalSebelumnya;
        Session::forget($keyLama);

        // Langkah 2: Update tanggalSebelumnya ke tanggal yang baru aktif
        // agar siap dipakai jika user ganti tanggal lagi
        $this->tanggalSebelumnya = $this->tanggal;

        $this->loadKandangs();
        $this->loadPakanByTanggal();
        $this->loadExistingDataByTanggal();
    }

    public function updated($propertyName): void
    {
        if (str_starts_with($propertyName, 'gridData') || str_starts_with($propertyName, 'kandangPakan')) {
            $this->recalculate();

            // ✅ TAMBAHAN: Simpan ke session setiap ada perubahan input
            // Sama persis seperti ProduksiPakan yang saveToSession() di updated()
            // Hanya simpan jika data belum ada di DB (belum di-save)
            if (! $this->produksiTelurId) {
                $this->saveToSession();
            }
        }
    }

    public function recalculate(): void
    {
        $this->kandangTotals = [];
        $this->grandTotal = ['butir' => 0, 'kilo' => 0.00, 'tray' => 0];

        foreach ($this->kandangs as $kandang) {
            $idKandang = $kandang['id'];
            $this->kandangTotals[$idKandang] = ['butir' => 0, 'kilo' => 0.00, 'tray' => 0];

            if (isset($this->gridData[$idKandang])) {
                foreach ($this->gridData[$idKandang] as $row) {
                    $butir = (int) ($row['butir'] ?? 0);
                    $kilo  = (float) ($row['kilo'] ?? 0);
                    $tray  = (float) ($row['tray'] ?? 0);

                    $this->kandangTotals[$idKandang]['butir'] += $butir;
                    $this->kandangTotals[$idKandang]['kilo']  += $kilo;
                    $this->kandangTotals[$idKandang]['tray']  += $tray;

                    $this->grandTotal['butir'] += $butir;
                    $this->grandTotal['kilo']  += $kilo;
                    $this->grandTotal['tray']  += $tray;
                }
            }
        }
    }

    // ─── Evaluasi Izin Pengeditan & Validasi ─────────────────

    protected function computePermissions($produksi = null): void
    {
        $this->isSuperAdmin   = Auth::user()->hasRole('super_admin');

        // ✅ Selalu isi nama user yang sedang login
        $this->namaUserLogin  = Auth::user()->name;

        if (!$produksi) {
            $this->isLocked           = false;
            $this->isDraftSaved       = false;
            $this->isCreator          = false;
            $this->canEdit            = true;
            $this->showSaveButton     = true;
            $this->showValidateButton = false;
            $this->isEditable         = true;

            // Reset info penyimpan karena belum ada data
            $this->namaPenyimpan  = '';
            $this->namaValidator  = '';
            $this->waktuValidasi  = '';
            return;
        }

        $this->isDraftSaved = true;
        $this->isLocked     = (bool) $produksi->is_validated;
        $this->isCreator = ($produksi->created_by == (string) Auth::id());

        // ✅ Ambil nama penyimpan dari kolom created_by di tabel produksi_telurs
        // Kamu perlu cek apakah created_by menyimpan ID atau nama
        // Jika menyimpan ID → ambil nama dari tabel users
        // Jika menyimpan nama langsung → pakai langsung
        $this->namaPenyimpan = \App\Models\User::find($produksi->created_by)?->name
            ?? $produksi->created_by   // fallback jika tidak ketemu di tabel users
            ?? 'Tidak diketahui';

        // ✅ Ambil nama validator dan waktu validasi
        $this->namaValidator = $produksi->validated_by ?? '';
        $this->waktuValidasi = $produksi->validated_at
            ? \Carbon\Carbon::parse($produksi->validated_at)->format('d M Y H:i')
            : '';

        // ... sisa logika permission yang sudah ada tidak berubah ...
        if ($this->isSuperAdmin) {
            $this->canEdit            = true;
            $this->showSaveButton     = true;
            $this->showValidateButton = !$this->isLocked;
            $this->isEditable         = true;
            return;
        }

        if ($this->isLocked) {
            $this->canEdit            = false;
            $this->showSaveButton     = false;
            $this->showValidateButton = false;
            $this->isEditable         = false;
            return;
        }

        if ($this->isDraftSaved) {
            if ($this->isCreator) {
                $this->canEdit            = false;
                $this->showSaveButton     = false;
                $this->showValidateButton = false;
                $this->isEditable         = false;
            } else {
                $this->canEdit            = true;
                $this->showSaveButton     = true;
                $this->showValidateButton = true;
                $this->isEditable         = true;
            }
            return;
        }

        $this->canEdit            = true;
        $this->showSaveButton     = true;
        $this->showValidateButton = false;
        $this->isEditable         = true;
    }

    public function save(): void
    {
        // ── Validasi 1: Tanggal wajib diisi ──
        $this->validate(['tanggal' => 'required|date']);

        if (! $this->isEditable) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Data sudah divalidasi.')
                ->danger()
                ->send();
            return;
        }

        // ── Validasi 2: Cek apakah minimal ada 1 baris yang diisi ──
        // Logika: loop semua gridData, jika tidak ada satupun baris
        // yang memiliki nilai > 0, maka tolak penyimpanan
        $adaData = false;

        foreach ($this->gridData as $idKandang => $rows) {
            foreach ($rows as $row) {
                $butir = (int) ($row['butir'] ?? 0);
                $kilo  = (float) ($row['kilo'] ?? 0);
                $tray  = (float) ($row['tray'] ?? 0);

                // Jika salah satu kolom ada isinya, tandai ada data
                if ($butir > 0 || $kilo > 0 || $tray > 0) {
                    $adaData = true;
                    break 2; // Keluar dari kedua loop sekaligus
                }
            }
        }

        // Jika tidak ada data sama sekali, hentikan dan beri tahu user
        if (! $adaData) {
            Notification::make()
                ->title('Data Kosong')
                ->body('Harap isi minimal satu baris data produksi telur sebelum menyimpan.')
                ->warning()
                ->send();
            return; // ← Berhenti di sini, tidak lanjut ke DB
        }

        $userId = Auth::id();

        DB::transaction(function () use ($userId) {
            $existing = ProduksiTelur::whereDate('tanggal', $this->tanggal)
                ->where('created_by', '!=', $userId)
                ->exists();

            if ($existing && ! $this->isSuperAdmin) {
                Notification::make()
                    ->title('Data Sudah Ada')
                    ->body('Data untuk tanggal ini sudah diinput oleh pengguna lain.')
                    ->danger()
                    ->send();
                return;
            }

            $produksi = ProduksiTelur::updateOrCreate(
                ['tanggal' => $this->tanggal],
                ['created_by' => $userId]
            );

            $this->produksiTelurId = $produksi->id;

            DetailProduksiTelur::where('id_produksi_telur', $produksi->id)->delete();

            foreach ($this->gridData as $idKandang => $rows) {
                $idPakanSelected = $this->kandangPakan[$idKandang] ?: null;

                foreach ($rows as $row) {
                    $butir = (int) ($row['butir'] ?? 0);
                    $kilo  = (float) ($row['kilo'] ?? 0);
                    $tray  = (float) ($row['tray'] ?? 0);

                    if ($butir === 0 && $kilo == 0 && $tray === 0) continue;

                    DetailProduksiTelur::create([
                        'id_produksi_telur'          => $produksi->id,
                        'id_kandang'                 => $idKandang,
                        'id_produksi_pakan_campuran' => $idPakanSelected,
                        'jumlah_telur_butir'         => $butir,
                        'jumlah_telur_kilo'          => $kilo,
                        'jumlah_telur_tray'          => $tray,
                    ]);
                }
            }

            if (method_exists($produksi, 'recalculateTotals')) {
                $produksi->recalculateTotals();
            }
        });

        // ✅ Perbaikan: Tampilkan NAMA user, bukan ID angka
        $namaUser = Auth::user()->name;
        Notification::make()
            ->title('Data Berhasil Disimpan')
            ->body("Disimpan oleh: {$namaUser}")
            ->success()
            ->send();

        Session::forget($this->sessionKey());

        $this->dispatch('data-saved');
        $this->loadExistingDataByTanggal();
    }

    public function validateProduksi(): void
    {
        if (! $this->produksiTelurId) {
            Notification::make()
                ->title('Simpan data terlebih dahulu sebelum memvalidasi.')
                ->warning()
                ->send();
            return;
        }

        $produksi = ProduksiTelur::findOrFail($this->produksiTelurId);

        try {
            DB::transaction(function () use ($produksi) {
                if (method_exists($produksi, 'validate')) {
                    $produksi->validate();
                } else {
                    $this->validate(['tanggal' => 'required|date']);
                    $produksi->update([
                        'is_validated' => true,
                        'validated_by' => Auth::user()->name,
                        'validated_at' => now(),
                    ]);
                }

                // Buat jurnal pembantu secara harian
                app(\App\Services\ProduksiTelurService::class)
                    ->buatJurnalDariProduksi($produksi, Auth::id());
            });
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Memvalidasi Data')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
            return;
        }

        $this->is_validated = true;
        $this->loadExistingDataByTanggal();

        Notification::make()
            ->title('Data produksi telur berhasil divalidasi.')
            ->success()
            ->send();
    }
}
