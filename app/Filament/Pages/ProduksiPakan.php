<?php

namespace App\Filament\Pages;

use App\Models\ProduksiPakan;
use App\Models\ProduksiPakanMentah;
use App\Models\ProduksiPakanCampuran;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ProduksiPakanLaporan  extends Page
{
    use HasPageShield;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Produksi Pakan';
    protected static ?string $title = 'Laporan Produksi Pakan';
    protected string $view = 'filament.pages.produksi-pakan';

    public static function canAccess(): bool
    {
        return true; // sementara, untuk test
    }

    // State Utama
    public ?string $selectedDate = null;
    public ?ProduksiPakan $currentRecord = null;

    // State Tabel & Audit
    public array $mentahState = [];
    public array $campuranState = [];
    public ?string $keterangan = '';
    public bool $isLocked = false;

    public function mount(): void
    {
        // Default ke hari ini jika ingin langsung memuat data
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadDataByDate();
    }

    /**
     * Dipanggil setiap kali tanggal diubah di Flatpickr
     */
    public function updatedSelectedDate(): void
    {
        $this->loadDataByDate();
    }

    public function loadDataByDate(): void
    {
        if (!$this->selectedDate) return;

        $date = Carbon::parse($this->selectedDate)->toDateString();

        // Cari data pakan berdasarkan tanggal
        $this->currentRecord = ProduksiPakan::with(['pakanMentahs.barang', 'pakanCampurans.barang'])
            ->whereDate('tanggal_produksi', $date)
            ->first();

        if ($this->currentRecord) {
            $this->isLocked = !!$this->currentRecord->validated_by;
            $this->keterangan = $this->currentRecord->keterangan;

            // Map Bahan Mentah
            $this->mentahState = $this->currentRecord->pakanMentahs->map(fn($item) => [
                'id' => $item->id,
                'nama' => $item->barang?->nama_barang,
                'awal' => (float) $item->stok_awal,
                'p' => (float) $item->keluar_pullet,
                'l1' => (float) $item->keluar_l1,
                'l2' => (float) $item->keluar_l2,
                'akhir' => (float) $item->stok_akhir,
            ])->toArray();

            // Map Bahan Campuran
            $this->campuranState = $this->currentRecord->pakanCampurans->map(fn($item) => [
                'id' => $item->id,
                'nama' => $item->barang?->nama_barang,
                'awal' => (float) $item->stok_awal,
                'masuk' => (float) $item->masuk,
                'p' => (float) $item->keluar_pullet,
                'l1' => (float) $item->keluar_l1,
                'l2' => (float) $item->keluar_l2,
                'akhir' => (float) $item->stok_akhir,
            ])->toArray();
        } else {
            // Reset jika data tidak ditemukan pada tanggal tersebut
            $this->resetStates();
        }
    }

    private function resetStates()
    {
        $this->currentRecord = null;
        $this->mentahState = [];
        $this->campuranState = [];
        $this->keterangan = '';
        $this->isLocked = false;
    }

    public function updated($propertyName): void
    {
        if (str_starts_with($propertyName, 'mentahState')) {
            $this->recalculateAll();
        }
    }

    private function recalculateAll(): void
    {
        $totalP = 0;
        $totalL1 = 0;
        $totalL2 = 0;

        foreach ($this->mentahState as $idx => $item) {
            $sisa = $item['awal'] - ($item['p'] + $item['l1'] + $item['l2']);
            $this->mentahState[$idx]['akhir'] = $sisa;

            $totalP += $item['p'];
            $totalL1 += $item['l1'];
            $totalL2 += $item['l2'];
        }

        // Sinkronisasi ke Campuran (Biasanya Pakan Pullet di idx 0, L1 di idx 1, dst)
        if (isset($this->campuranState[0])) $this->campuranState[0]['masuk'] = $totalP;
        if (isset($this->campuranState[1])) $this->campuranState[1]['masuk'] = $totalL1;
        if (isset($this->campuranState[2])) $this->campuranState[2]['masuk'] = $totalL2;

        foreach ($this->campuranState as $idx => $item) {
            $this->campuranState[$idx]['akhir'] = ($item['awal'] + $item['masuk']) - ($item['p'] + $item['l1'] + $item['l2']);
        }
    }

    public function save(): void
    {
        if (!$this->currentRecord || $this->isLocked) return;

        DB::transaction(function () {
            foreach ($this->mentahState as $data) {
                ProduksiPakanMentah::where('id', $data['id'])->update([
                    'keluar_pullet' => $data['p'],
                    'keluar_l1' => $data['l1'],
                    'keluar_l2' => $data['l2'],
                    'stok_akhir' => $data['akhir'],
                ]);
            }

            foreach ($this->campuranState as $data) {
                ProduksiPakanCampuran::where('id', $data['id'])->update([
                    'masuk' => $data['masuk'],
                    'stok_akhir' => $data['akhir'],
                ]);
            }

            $this->currentRecord->update(['keterangan' => $this->keterangan]);
        });

        Notification::make()->title('Data Berhasil Disimpan')->success()->send();
    }

    public function validateData(): void
    {
        if (!$this->currentRecord || $this->isLocked) return;

        $this->save();
        $this->currentRecord->update(['validated_by' => Auth::user()->name]);
        $this->isLocked = true;

        Notification::make()->title('Laporan Divalidasi')->body('Data telah dikunci permanen.')->success()->send();
    }
}
