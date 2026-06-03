<?php

namespace App\Filament\Resources\Ayams\Pages;

use App\Filament\Resources\Ayams\AyamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAyams extends ListRecords
{
    protected static string $resource = AyamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
