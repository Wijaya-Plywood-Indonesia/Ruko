<?php

namespace App\Filament\Resources\Supliers\Pages;

use App\Filament\Resources\Supliers\SupliersResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSupliers extends ViewRecord
{
    protected static string $resource = SupliersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
