<?php

namespace App\Filament\Resources\Supliers\Pages;

use App\Filament\Resources\Supliers\SupliersResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupliers extends ListRecords
{
    protected static string $resource = SupliersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
