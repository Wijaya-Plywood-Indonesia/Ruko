<?php

namespace App\Filament\Resources\ReturnPenjualans\Pages;

use App\Filament\Resources\ReturnPenjualans\ReturnPenjualanResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReturnPenjualans extends ListRecords
{
    protected static string $resource = ReturnPenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),

            Action::make('formRetur')
                ->label('Form Retur Barang')
                ->icon('heroicon-o-document-text')
                ->color('info')
                // Gunakan static method getUrl langsung dari Class Page-nya
                ->url(fn(): string => FormReturnPenjualan::getUrl())
                // Opsi tambahan agar loading lebih smooth (ciri khas SPA Laravel)
                ->extraAttributes([
                    'x-on:click' => "window.location.href = '" . FormReturnPenjualan::getUrl() . "'",
                ])
        ];
    }
}
