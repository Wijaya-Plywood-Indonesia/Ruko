<?php

namespace App\Filament\Resources\ProduksiTelurs\Pages;

use App\Filament\Resources\ProduksiTelurs\ProduksiTelurResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProduksiTelur extends EditRecord
{
    protected static string $resource = ProduksiTelurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
