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

    public string $periodeAwal;
    public string $periodeAkhir;
    public bool $tampilkanSaldoNol = false;

    public function mount(): void
    {
        $now = now();
        $this->periodeAwal  = $now->format('Y-m');
        $this->periodeAkhir = $now->format('Y-m');
    }

    #[Computed]
    public function neracaMulti(): array
    {
        $periodeList = $this->buildPeriodeList();
        if (empty($periodeList)) return [];

        return app(NeracaService::class)->hitungMulti($periodeList);
    }

    public function buildPeriodeList(): array
    {
        try {
            $awal  = Carbon::createFromFormat('Y-m', $this->periodeAwal)->startOfMonth();
            $akhir = Carbon::createFromFormat('Y-m', $this->periodeAkhir)->startOfMonth();
        } catch (\Exception $e) {
            return [];
        }

        if ($awal->gt($akhir)) return [];

        if ($awal->diffInMonths($akhir) > 11) {
            $akhir = $awal->copy()->addMonths(11);
        }

        $list    = [];
        $current = $awal->copy();

        while ($current->lte($akhir)) {
            $list[] = [
                'tahun' => (int) $current->format('Y'),
                'bulan' => (int) $current->format('n'),
            ];
            $current->addMonth();
        }

        return $list;
    }

    public function jumlahPeriode(): int
    {
        return count($this->buildPeriodeList());
    }

    public function periodeValid(): bool
    {
        try {
            $awal  = Carbon::createFromFormat('Y-m', $this->periodeAwal);
            $akhir = Carbon::createFromFormat('Y-m', $this->periodeAkhir);
            return $awal->lte($akhir);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Return type mixed agar kompatibel dengan Livewire:
     * Excel::download() mengembalikan BinaryFileResponse,
     * tapi Livewire juga perlu bisa return null tanpa error type-hint.
     */
    public function exportExcel(): mixed
    {
        $periodeList = $this->buildPeriodeList();

        if (empty($periodeList)) {
            return null;
        }

        if (count($periodeList) === 1) {
            $p        = $periodeList[0];
            $filename = 'Neraca_' . $p['tahun'] . '-' . str_pad($p['bulan'], 2, '0', STR_PAD_LEFT) . '.xlsx';
        } else {
            $first    = $periodeList[0];
            $last     = $periodeList[count($periodeList) - 1];
            $filename = 'Neraca_'
                . $first['tahun'] . '-' . str_pad($first['bulan'], 2, '0', STR_PAD_LEFT)
                . '_sd_'
                . $last['tahun'] . '-' . str_pad($last['bulan'], 2, '0', STR_PAD_LEFT)
                . '.xlsx';
        }

        return Excel::download(
            new NeracaExport($periodeList, $this->tampilkanSaldoNol),
            $filename
        );
    }
}