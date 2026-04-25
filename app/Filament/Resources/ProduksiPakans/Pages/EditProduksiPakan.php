<?php

namespace App\Filament\Resources\ProduksiPakans\Pages;

use App\Filament\Resources\ProduksiPakans\ProduksiPakanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProduksiPakan extends EditRecord
{
    protected static string $resource = ProduksiPakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
