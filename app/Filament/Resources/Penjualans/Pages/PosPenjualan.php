<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Filament\Resources\Penjualans\PenjualanResource;
use Filament\Resources\Pages\Page;

class PosPenjualan extends Page
{
    protected static string $resource = PenjualanResource::class;

    protected string $view = 'filament.resources.penjualans.pages.pos-penjualan';

    protected static ?string $title = 'Point of Sale';

    protected static bool $shouldAuthorizeResource = false;

    // ✅ INI WAJIB
    protected function authorizeAccess(): void
    {
        // bypass semua authorization
    }

    public static function canAccess(array $parameters = []): bool
    {
        return true;
    }

    public function getLayout(): string
    {
        return 'filament::layouts.base';
    }
}
