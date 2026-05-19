<?php

namespace App\Filament\Pages;

use App\Exports\NeracaExport;
use App\Services\NeracaService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class NeracaPage extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Neraca Telur';
    protected static UnitEnum|string|null $navigationGroup = 'Akuntansi Telur';
    protected static ?string $title = 'Neraca Telur';
    protected string $view = 'filament.pages.neraca-page';

    // Properti filter dinamis
    public string $jenisFilter = 'bulan'; // Default 'bulan', opsi lain 'hari'
    public string $periodeAwal;
    public string $periodeAkhir;
    public bool $tampilkanSaldoNol = false;

    public function mount(): void
    {
        $now = now();
        $this->periodeAwal  = $now->format('Y-m');
        $this->periodeAkhir = $now->format('Y-m');
    }

    // Hook saat filter jenis (hari/bulan) diubah lewat UI
    public function updatedJenisFilter($value): void
    {
        $now = now();
        if ($value === 'hari') {
            $this->periodeAwal  = $now->startOfMonth()->format('Y-m-d');
            $this->periodeAkhir = $now->endOfMonth()->format('Y-m-d');
        } else {
            $this->periodeAwal  = $now->format('Y-m');
            $this->periodeAkhir = $now->format('Y-m');
        }
    }

    #[Computed]
    public function neracaMulti(): array
    {
        $periodeList = $this->buildPeriodeList();
        if (empty($periodeList)) return [];

        // Kirim $periodeList beserta jenis filternya ke Service
        return app(NeracaService::class)->hitungMulti($periodeList, $this->jenisFilter);
    }

    public function buildPeriodeList(): array
    {
        $list = [];

        try {
            if ($this->jenisFilter === 'hari') {
                $awal  = Carbon::createFromFormat('Y-m-d', $this->periodeAwal)->startOfDay();
                $akhir = Carbon::createFromFormat('Y-m-d', $this->periodeAkhir)->startOfDay();
                
                if ($awal->gt($akhir)) return [];

                // Batasi maksimal penarikan harian 31 hari
                if ($awal->diffInDays($akhir) > 31) {
                    $akhir = $awal->copy()->addDays(31);
                }

                $current = $awal->copy();
                while ($current->lte($akhir)) {
                    $list[] = [
                        'date_string' => $current->format('Y-m-d'),
                        'label'       => $current->locale('id')->isoFormat('DD MMM Y'),
                        'start'       => $current->copy()->startOfDay(),
                        'end'         => $current->copy()->endOfDay(),
                        'tahun'       => (int) $current->format('Y'),
                        'bulan'       => (int) $current->format('n'),
                    ];
                    $current->addDay();
                }
            } else {
                $awal  = Carbon::createFromFormat('Y-m', $this->periodeAwal)->startOfMonth();
                $akhir = Carbon::createFromFormat('Y-m', $this->periodeAkhir)->startOfMonth();

                if ($awal->gt($akhir)) return [];

                // Batasi maksimal penarikan bulanan 12 bulan
                if ($awal->diffInMonths($akhir) > 11) {
                    $akhir = $awal->copy()->addMonths(11);
                }

                $current = $awal->copy();
                while ($current->lte($akhir)) {
                    $list[] = [
                        'date_string' => $current->format('Y-m'),
                        'label'       => $current->locale('id')->isoFormat('MMMM Y'),
                        'start'       => $current->copy()->startOfMonth(),
                        'end'         => $current->copy()->endOfMonth(),
                        'tahun'       => (int) $current->format('Y'),
                        'bulan'       => (int) $current->format('n'),
                    ];
                    $current->addMonth();
                }
            }
        } catch (\Exception $e) {
            return [];
        }

        return $list;
    }

    // Fungsi khusus untuk mereset filter saat tombol diklik
    public function ubahJenisFilter(string $jenis): void
    {
        $this->jenisFilter = $jenis;
        $now = now();
        
        if ($jenis === 'hari') {
            // Jika pindah ke harian, set ke tgl 1 s.d hari ini
            $this->periodeAwal  = $now->startOfMonth()->format('Y-m-d');
            $this->periodeAkhir = $now->format('Y-m-d'); 
        } else {
            // Jika pindah ke bulanan, set ke bulan ini
            $this->periodeAwal  = $now->format('Y-m');
            $this->periodeAkhir = $now->format('Y-m');
        }
    }

    public function jumlahPeriode(): int
    {
        return count($this->buildPeriodeList());
    }

    public function periodeValid(): bool
    {
        try {
            $format = $this->jenisFilter === 'hari' ? 'Y-m-d' : 'Y-m';
            $awal   = Carbon::createFromFormat($format, $this->periodeAwal);
            $akhir  = Carbon::createFromFormat($format, $this->periodeAkhir);
            return $awal->lte($akhir);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function exportExcel(): mixed
    {
        $periodeList = $this->buildPeriodeList();

        if (empty($periodeList)) {
            return null;
        }

        $first = $periodeList[0];
        $last  = $periodeList[count($periodeList) - 1];

        if (count($periodeList) === 1) {
            $filename = 'Neraca_' . $first['date_string'] . '.xlsx';
        } else {
            $filename = 'Neraca_' . $first['date_string'] . '_sd_' . $last['date_string'] . '.xlsx';
        }

        // Pastikan parameter Export Excel kamu menyesuaikan jika ada perubahan struktur
        return Excel::download(
            new NeracaExport($periodeList, $this->tampilkanSaldoNol),
            $filename
        );
    }
}