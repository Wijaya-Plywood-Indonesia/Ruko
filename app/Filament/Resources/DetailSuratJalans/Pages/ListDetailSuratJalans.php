<?php

namespace App\Filament\Resources\DetailSuratJalans\Pages;

use App\Filament\Resources\DetailSuratJalans\DetailSuratJalanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDetailSuratJalans extends ListRecords
{
    protected static string $resource = DetailSuratJalanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
