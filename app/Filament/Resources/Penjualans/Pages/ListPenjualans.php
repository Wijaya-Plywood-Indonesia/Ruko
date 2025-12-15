<?php

namespace App\Filament\Resources\Penjualans\Pages;

use App\Filament\Resources\Penjualans\PenjualanResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPenjualans extends ListRecords
{
    protected static string $resource = PenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('pos')
                ->label('POS')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->url(PenjualanResource::getUrl('pos'))
                ->openUrlInNewTab(false)

            ,
        ];
    }
}
