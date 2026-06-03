<?php

namespace App\Filament\Resources\Kandangs\Pages;

use App\Filament\Resources\Kandangs\KandangResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKandangs extends ListRecords
{
    protected static string $resource = KandangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
