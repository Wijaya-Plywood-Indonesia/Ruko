<?php

namespace App\Filament\Resources\ProduksiTelurs\Pages;

use App\Filament\Resources\ProduksiTelurs\ProduksiTelurResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProduksiTelur extends ViewRecord
{
    protected static string $resource = ProduksiTelurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
