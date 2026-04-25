<?php

namespace App\Filament\Resources\ProduksiPakans\Pages;

use App\Filament\Resources\ProduksiPakans\ProduksiPakanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProduksiPakan extends ViewRecord
{
    protected static string $resource = ProduksiPakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
