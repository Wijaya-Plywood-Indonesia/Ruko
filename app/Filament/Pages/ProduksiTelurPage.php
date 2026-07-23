<?php

namespace App\Filament\Pages;

use App\Models\DetailProduksiTelur;
use App\Models\Kandang;
use App\Models\ProduksiPakanCampuran;
use App\Models\ProduksiTelur;
use App\Models\User;
use App\Services\ProduksiTelurService;
use Carbon\Carbon;
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

    public string $namaUserLogin = '';

    public string $namaPenyimpan = '';

    public string $namaValidator = '';

    public string $waktuValidasi = '';

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

    // ─── Input Hasil Kandang ─────────────────────────────────

    /**
     * Data input hasil kandang (Peti, Kiloan, Sisa, Bentes)
     */
    public array $hasilKandang = [
        'peti' => ['hasil' => 0, 'satuan' => 'P'],
        'kiloan' => ['hasil' => 0, 'satuan' => 'kg'],
        'sisa' => ['hasil' => 0, 'satuan' => 'kg'],
        'bentes' => ['hasil' => 0, 'satuan' => 'kg'],
    ];

    /**
     * Konversi: 1 Peti = berapa kg.
     * Sesuaikan dengan standar operasional Anda.
     */
    public float $petiToKg = 10.0;

    /** Total kg dari hasil kandang (Peti*petiToKg + Kiloan + Sisa + Bentes) */
    public float $hasilKandangTotal = 0;

    /** Total kilo dari grid input atas (otomatis dari grandTotal['kilo']) */
    public float $hasilKandangDariKandang = 0;

    /** Selisih = Dari Kandang - Total Hasil Kandang */
    public float $hasilKandangSelisih = 0;

    // ─── Status & Izin Pengeditan ───────────────────────────

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

    public int $maxRows = 10;

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
        return 'pt_draft_'.Auth::id().'_'.($this->tanggal ?? 'nodate');
    }

    private function saveToSession(): void
    {
        Session::put($this->sessionKey(), [
            'gridData' => $this->gridData,
            'kandangPakan' => $this->kandangPakan,
            'hasilKandang' => $this->hasilKandang,
        ]);
    }

    private function restoreFromSession(): void
    {
        $draft = Session::get($this->sessionKey());
        if (! $draft) {
            return;
        }

        foreach ($this->kandangs as $kandang) {
            $id = $kandang['id'];

            if (isset($draft['gridData'][$id])) {
                foreach ($draft['gridData'][$id] as $rowIdx => $row) {
                    $this->gridData[$id][$rowIdx] = [
                        'id' => null,
                        'butir' => (int) ($row['butir'] ?? 0),
                        'kilo' => (float) ($row['kilo'] ?? 0),
                        'tray' => (float) ($row['tray'] ?? 0),
                    ];
                }
            }

            if (isset($draft['kandangPakan'][$id])) {
                $this->kandangPakan[$id] = $draft['kandangPakan'][$id];
            }
        }

        // Restore hasil kandang dari session
        if (isset($draft['hasilKandang'])) {
            foreach (['peti', 'kiloan', 'sisa', 'bentes'] as $key) {
                if (isset($draft['hasilKandang'][$key])) {
                    $this->hasilKandang[$key]['hasil'] = (float) ($draft['hasilKandang'][$key]['hasil'] ?? 0);
                }
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
        $produksi = ProduksiTelur::whereDate('tanggal', $this->tanggal)->first();

        if (! $produksi) {
            $this->resetMatrix();
            $this->restoreFromSession();
            $this->recalculate();

            return;
        }

        $this->produksiTelurId = $produksi->id;
        $this->is_validated = (bool) $produksi->is_validated;

        $this->computePermissions($produksi);

        $details = DetailProduksiTelur::where('id_produksi_telur', $produksi->id)->get();

        foreach ($this->kandangs as $kandang) {
            $idKandang = $kandang['id'];
            $this->gridData[$idKandang] = [];
            $this->kandangPakan[$idKandang] = null;

            $kandangDetails = $details->where('id_kandang', $idKandang)->values();

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
                        'id' => null, 'butir' => 0, 'kilo' => 0, 'tray' => 0,
                    ];
                }
            }
        }

        // ─── Load Hasil Kandang dari DB ───────────────────────
        // Kolom asli di tabel produksi_telurs: hasil_peti, hasil_kiloan, hasil_sisa, hasil_bentes
        $this->hasilKandang['peti']['hasil'] = (float) ($produksi->hasil_peti ?? 0);
        $this->hasilKandang['kiloan']['hasil'] = (float) ($produksi->hasil_kiloan ?? 0);
        $this->hasilKandang['sisa']['hasil'] = (float) ($produksi->hasil_sisa ?? 0);
        $this->hasilKandang['bentes']['hasil'] = (float) ($produksi->hasil_bentes ?? 0);

        $this->recalculate();
    }

    protected function resetMatrix(): void
    {
        foreach ($this->kandangs as $kandang) {
            $id = $kandang['id'];
            $this->gridData[$id] = [];
            for ($i = 0; $i < $this->maxRows; $i++) {
                $this->gridData[$id][$i] = [
                    'id' => null, 'butir' => 0, 'kilo' => 0, 'tray' => 0,
                ];
            }
            $this->kandangPakan[$id] = null;
        }

        // Reset hasil kandang
        $this->hasilKandang = [
            'peti' => ['hasil' => 0, 'satuan' => 'P'],
            'kiloan' => ['hasil' => 0, 'satuan' => 'kg'],
            'sisa' => ['hasil' => 0, 'satuan' => 'kg'],
            'bentes' => ['hasil' => 0, 'satuan' => 'kg'],
        ];

        $this->produksiTelurId = null;
        $this->is_validated = false;
        $this->isEditable = true;
        $this->kandangTotals = [];
        $this->grandTotal = ['butir' => 0, 'kilo' => 0.00, 'tray' => 0];

        $this->hasilKandangTotal = 0;
        $this->hasilKandangDariKandang = 0;
        $this->hasilKandangSelisih = 0;

        $this->computePermissions(null);
    }

    // ─── Real-Time Listeners & Recalculate ───────────────────

    public function updatedTanggal(): void
    {
        $keyLama = 'pt_draft_'.Auth::id().'_'.$this->tanggalSebelumnya;
        Session::forget($keyLama);

        $this->tanggalSebelumnya = $this->tanggal;

        $this->loadKandangs();
        $this->loadPakanByTanggal();
        $this->loadExistingDataByTanggal();
    }

    public function updated($propertyName): void
    {
        if (
            str_starts_with($propertyName, 'gridData') ||
            str_starts_with($propertyName, 'kandangPakan') ||
            str_starts_with($propertyName, 'hasilKandang')
        ) {
            $this->recalculate();

            if (! $this->produksiTelurId) {
                $this->saveToSession();
            }
        }
    }

    public function recalculate(): void
    {
        // ─── Recalculate grid totals ─────────────────────────
        $this->kandangTotals = [];
        $this->grandTotal = ['butir' => 0, 'kilo' => 0.00, 'tray' => 0];

        foreach ($this->kandangs as $kandang) {
            $idKandang = $kandang['id'];
            $this->kandangTotals[$idKandang] = ['butir' => 0, 'kilo' => 0.00, 'tray' => 0];

            if (isset($this->gridData[$idKandang])) {
                foreach ($this->gridData[$idKandang] as $row) {
                    $butir = (int) ($row['butir'] ?? 0);
                    $kilo = (float) ($row['kilo'] ?? 0);
                    $tray = (float) ($row['tray'] ?? 0);

                    $this->kandangTotals[$idKandang]['butir'] += $butir;
                    $this->kandangTotals[$idKandang]['kilo'] += $kilo;
                    $this->kandangTotals[$idKandang]['tray'] += $tray;

                    $this->grandTotal['butir'] += $butir;
                    $this->grandTotal['kilo'] += $kilo;
                    $this->grandTotal['tray'] += $tray;
                }
            }
        }

        // ─── Recalculate Hasil Kandang ────────────────────────
        $peti = (float) ($this->hasilKandang['peti']['hasil'] ?? 0);
        $kiloan = (float) ($this->hasilKandang['kiloan']['hasil'] ?? 0);
        $sisa = (float) ($this->hasilKandang['sisa']['hasil'] ?? 0);
        $bentes = (float) ($this->hasilKandang['bentes']['hasil'] ?? 0);

        // Total: Peti dikonversi ke kg, sisanya sudah kg
        $this->hasilKandangTotal = ($peti * $this->petiToKg) + $kiloan + $sisa + $bentes;

        // Dari Kandang = total kilo dari grid input atas
        $this->hasilKandangDariKandang = $this->grandTotal['kilo'];

        // Selisih = Dari Kandang - Total Hasil Kandang
        $this->hasilKandangSelisih = $this->hasilKandangDariKandang - $this->hasilKandangTotal;
    }

    // ─── Evaluasi Izin Pengeditan & Validasi ─────────────────

    protected function computePermissions($produksi = null): void
    {
        $this->isSuperAdmin = Auth::user()->hasRole('super_admin');
        $this->namaUserLogin = Auth::user()->name;

        if (! $produksi) {
            $this->isLocked = false;
            $this->isDraftSaved = false;
            $this->isCreator = false;
            $this->canEdit = true;
            $this->showSaveButton = true;
            $this->showValidateButton = false;
            $this->isEditable = true;
            $this->namaPenyimpan = '';
            $this->namaValidator = '';
            $this->waktuValidasi = '';

            return;
        }

        $this->isDraftSaved = true;
        $this->isLocked = (bool) $produksi->is_validated;
        $this->isCreator = ($produksi->created_by == (string) Auth::id());

        $this->namaPenyimpan = User::find($produksi->created_by)?->name
            ?? $produksi->created_by
            ?? 'Tidak diketahui';

        $this->namaValidator = $produksi->validated_by ?? '';
        $this->waktuValidasi = $produksi->validated_at
            ? Carbon::parse($produksi->validated_at)->format('d M Y H:i')
            : '';

        if ($this->isSuperAdmin) {
            $this->canEdit = true;
            $this->showSaveButton = true;
            $this->showValidateButton = ! $this->isLocked;
            $this->isEditable = true;

            return;
        }

        if ($this->isLocked) {
            $this->canEdit = false;
            $this->showSaveButton = false;
            $this->showValidateButton = false;
            $this->isEditable = false;

            return;
        }

        if ($this->isDraftSaved) {
            if ($this->isCreator) {
                $this->canEdit = false;
                $this->showSaveButton = false;
                $this->showValidateButton = false;
                $this->isEditable = false;
            } else {
                $this->canEdit = true;
                $this->showSaveButton = true;
                $this->showValidateButton = true;
                $this->isEditable = true;
            }

            return;
        }

        $this->canEdit = true;
        $this->showSaveButton = true;
        $this->showValidateButton = false;
        $this->isEditable = true;
    }

    public function save(): void
    {
        $this->validate(['tanggal' => 'required|date']);

        if (! $this->isEditable) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Data sudah divalidasi.')
                ->danger()
                ->send();

            return;
        }

        $adaData = false;
        foreach ($this->gridData as $idKandang => $rows) {
            foreach ($rows as $row) {
                if ((int) ($row['butir'] ?? 0) > 0 || (float) ($row['kilo'] ?? 0) > 0 || (float) ($row['tray'] ?? 0) > 0) {
                    $adaData = true;
                    break 2;
                }
            }
        }

        if (! $adaData) {
            Notification::make()
                ->title('Data Kosong')
                ->body('Harap isi minimal satu baris data produksi telur sebelum menyimpan.')
                ->warning()
                ->send();

            return;
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

            // ─── Simpan Hasil Kandang ke kolom asli (hasil_peti, hasil_kiloan, hasil_sisa, hasil_bentes) ───
            $produksi = ProduksiTelur::updateOrCreate(
                ['tanggal' => $this->tanggal],
                [
                    'created_by' => $userId,
                    'hasil_peti' => (float) ($this->hasilKandang['peti']['hasil'] ?? 0),
                    'hasil_kiloan' => (float) ($this->hasilKandang['kiloan']['hasil'] ?? 0),
                    'hasil_sisa' => (float) ($this->hasilKandang['sisa']['hasil'] ?? 0),
                    'hasil_bentes' => (float) ($this->hasilKandang['bentes']['hasil'] ?? 0),
                ]
            );

            $this->produksiTelurId = $produksi->id;

            DetailProduksiTelur::where('id_produksi_telur', $produksi->id)->delete();

            foreach ($this->gridData as $idKandang => $rows) {
                $idPakanSelected = $this->kandangPakan[$idKandang] ?: null;

                foreach ($rows as $row) {
                    $butir = (int) ($row['butir'] ?? 0);
                    $kilo = (float) ($row['kilo'] ?? 0);
                    $tray = (float) ($row['tray'] ?? 0);

                    if ($butir === 0 && $kilo == 0 && $tray === 0) {
                        continue;
                    }

                    DetailProduksiTelur::create([
                        'id_produksi_telur' => $produksi->id,
                        'id_kandang' => $idKandang,
                        'id_produksi_pakan_campuran' => $idPakanSelected,
                        'jumlah_telur_butir' => $butir,
                        'jumlah_telur_kilo' => $kilo,
                        'jumlah_telur_tray' => $tray,
                    ]);
                }
            }

            if (method_exists($produksi, 'recalculateTotals')) {
                $produksi->recalculateTotals();
            }
        });

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

                app(ProduksiTelurService::class)
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
