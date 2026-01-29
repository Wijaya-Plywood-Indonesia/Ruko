<?php

namespace App\Filament\Resources\StokLogs\Pages;

use App\Filament\Resources\StokLogs\StokLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStokLogs extends ListRecords
{
    protected static string $resource = StokLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
