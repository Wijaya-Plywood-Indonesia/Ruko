<?php

namespace App\Filament\Resources\ProduksiPakans\Pages;

use App\Filament\Resources\ProduksiPakans\ProduksiPakanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProduksiPakans extends ListRecords
{
    protected static string $resource = ProduksiPakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
