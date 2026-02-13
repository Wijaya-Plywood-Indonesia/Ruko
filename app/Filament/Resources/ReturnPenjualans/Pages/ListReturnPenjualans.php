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
                ->icon('heroicon-o-document')
                ->color('info')
                ->url(ReturnPenjualanResource::getUrl('form'))
                ->openUrlInNewTab(false)

        ];
    }
}
