<?php

namespace App\Filament\Resources\Supliers\Pages;

use App\Filament\Resources\Supliers\SupliersResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSupliers extends EditRecord
{
    protected static string $resource = SupliersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
