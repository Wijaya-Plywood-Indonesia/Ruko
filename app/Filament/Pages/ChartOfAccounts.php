<?php

namespace App\Filament\Pages;

use App\Models\IndukAkun;
use Filament\Pages\Page;
use UnitEnum;
use Illuminate\Support\Collection;

class ChartOfAccounts extends Page
{
    protected static ?string $navigationLabel = 'Chart of Accounts';
    protected static ?string $title = 'Chart of Accounts';
    protected static string|UnitEnum|null $navigationGroup = 'Akuntansi';
    protected static ?int $navigationSort = 4;

    public function getView(): string
    {
        return 'filament.pages.chart-of-accounts';
    }

    public string $search = '';
    public array $expandedInduk = [];
    public array $expandedAnak = [];
    public bool $allExpanded = false;

    public function mount(): void
    {
        // Default: semua di-collapse
        $this->expandedInduk = [];
        $this->expandedAnak = [];
    }

    public function toggleInduk(int $id): void
    {
        if (in_array($id, $this->expandedInduk)) {
            $this->expandedInduk = array_values(array_filter($this->expandedInduk, fn($i) => $i !== $id));
        } else {
            $this->expandedInduk[] = $id;
        }
    }

    public function toggleAnak(int $id): void
    {
        if (in_array($id, $this->expandedAnak)) {
            $this->expandedAnak = array_values(array_filter($this->expandedAnak, fn($i) => $i !== $id));
        } else {
            $this->expandedAnak[] = $id;
        }
    }

    public function expandAll(): void
    {
        $data = $this->getChartData();
        $this->expandedInduk = $data->pluck('id')->toArray();
        $allAnakIds = $data->flatMap(fn($induk) => $induk->anakAkuns)->pluck('id')->toArray();
        $this->expandedAnak = $allAnakIds;
        $this->allExpanded = true;
    }

    public function collapseAll(): void
    {
        $this->expandedInduk = [];
        $this->expandedAnak = [];
        $this->allExpanded = false;
    }

    public function getChartData(): Collection
    {
        return IndukAkun::with([
            'anakAkuns' => function ($q) {
                $q->whereNull('parent')->orderBy('kode_anak_akun')
                    ->with([
                        'children' => function ($q2) {
                            $q2->orderBy('kode_anak_akun')
                                ->with(['subAnakAkuns' => fn($q3) => $q3->orderBy('kode_sub_anak_akun')]);
                        },
                        'subAnakAkuns' => fn($q2) => $q2->orderBy('kode_sub_anak_akun'),
                    ]);
            },
        ])
            ->orderBy('kode_induk_akun')
            ->get()
            ->filter(function ($induk) {
                if (empty($this->search)) return true;
                $s = strtolower($this->search);
                if (str_contains(strtolower($induk->kode_induk_akun), $s)) return true;
                if (str_contains(strtolower($induk->nama_induk_akun), $s)) return true;
                foreach ($induk->anakAkuns as $anak) {
                    if (str_contains(strtolower($anak->kode_anak_akun), $s)) return true;
                    if (str_contains(strtolower($anak->nama_anak_akun), $s)) return true;
                    foreach ($anak->subAnakAkuns as $sub) {
                        if (str_contains(strtolower($sub->kode_sub_anak_akun), $s)) return true;
                        if (str_contains(strtolower($sub->nama_sub_anak_akun), $s)) return true;
                    }
                }
                return false;
            });
    }

    public function getTotalAkunCount(): int
    {
        return IndukAkun::count();
    }

    public function getTotalAnakCount(): int
    {
        return \App\Models\AnakAkun::count();
    }
}
