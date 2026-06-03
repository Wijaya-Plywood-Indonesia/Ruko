<?php

namespace App\Filament\Resources\ProduksiTelurs\Pages;

use App\Filament\Resources\ProduksiTelurs\ProduksiTelurResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProduksiTelurs extends ListRecords
{
    protected static string $resource = ProduksiTelurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
