<?php

namespace App\Filament\Resources\ListAkuns\Pages;

use App\Filament\Resources\ListAkuns\ListAkunResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListListAkuns extends ListRecords
{
    protected static string $resource = ListAkunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
