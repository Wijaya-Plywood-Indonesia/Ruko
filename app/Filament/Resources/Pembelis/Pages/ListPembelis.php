<?php

namespace App\Filament\Resources\Pembelis\Pages;

use App\Filament\Resources\Pembelis\PembeliResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPembelis extends ListRecords
{
    protected static string $resource = PembeliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
