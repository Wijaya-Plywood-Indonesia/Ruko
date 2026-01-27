<?php

namespace App\Filament\Pages;
use App\Services\Penjualans\LaporanPenjualanService;

use Filament\Pages\Page;

class LaporanNPenjualanPreview extends Page
{
    protected string $view = 'filament.pages.laporan-n-penjualan-preview';

    protected static string $resource = PenjualanResource::class;

    // protected string $view = 'filament.resources.penjualans.pages.laporan-penjualan-preview';

    public string $type = 'main';
    public ?string $from = null;
    public ?string $to = null;

    public array $data = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        // $this->data = app(LaporanPenjualanService::class)->get($this->type, $this->from, $this->to);
        // $this->data = LaporanPenjualanService::get($this->type, $this->from, $this->to);
        $this->data = app(LaporanPenjualanService::class)->get($this->type, $this->from, $this->to);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}





