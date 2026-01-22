<?php

namespace App\Filament\Resources\SuratJalans\Pages;

use App\Filament\Resources\SuratJalans\SuratJalanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSuratJalans extends ListRecords
{
    protected static string $resource = SuratJalanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
