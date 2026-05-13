<?php

namespace App\Filament\Pages;

use App\Services\NeracaService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use UnitEnum;

class NeracaPage extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Neraca Telur';
    protected static UnitEnum|string|null $navigationGroup = 'Akuntansi Telur';
    protected static ?string $title = 'Neraca Telur';
    protected string $view = 'filament.pages.neraca-page';

    // ── Filter state ──────────────────────────────────────────────────
    public string $periodeAwal;
    public string $periodeAkhir;

    public function mount(): void
    {
        $now = now();
        $this->periodeAwal  = $now->format('Y-m');
        $this->periodeAkhir = $now->format('Y-m');
    }

    // ── Computed ──────────────────────────────────────────────────────

    /**
     * Neraca multi-periode dari tabel buku_besar.
     */
    #[Computed]
    public function neracaMulti(): array
    {
        $periodeList = $this->buildPeriodeList();
        if (empty($periodeList)) return [];

        return app(NeracaService::class)->hitungMulti($periodeList);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function buildPeriodeList(): array
    {
        try {
            $awal  = Carbon::createFromFormat('Y-m', $this->periodeAwal)->startOfMonth();
            $akhir = Carbon::createFromFormat('Y-m', $this->periodeAkhir)->startOfMonth();
        } catch (\Exception $e) {
            return [];
        }

        if ($awal->gt($akhir)) return [];

        // Guard: maksimal 12 bulan
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
}