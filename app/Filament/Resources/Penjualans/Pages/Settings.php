<?php

namespace App\Filament\Resources\Penjualans\Pages;

use Illuminate\Contracts\View\View;
use App\Filament\Resources\Penjualans\PenjualanResource;
use App\Models\Penjualan;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class Settings extends Page
{
    protected static string $resource = PenjualanResource::class;

    protected string $view = 'filament.resources.penjualans.pages.settings';

    // Menggunakan layout blank agar tidak ada sidebar/navigasi
    protected static string $layout = 'components.layouts.blank';

    // Gunakan public property agar bisa diakses di Blade
    public Collection $allPenjualan;

    /**
     * INI KUNCINYA: Kita override method render() bawaan Filament
     * agar dia tidak membungkus view kita dengan layout Admin.
     */
public function render(): View
{
    /** @var \Illuminate\View\View $view */
    $view = view($this->view);

    return $view->layout('components.layouts.blank');
}
    public function mount(): void
    {
        $this->allPenjualan = Penjualan::all();
    }
}