<?php

namespace App\Filament\Resources\DetailSuratJalans\Pages;

use App\Filament\Resources\DetailSuratJalans\DetailSuratJalanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDetailSuratJalan extends EditRecord
{
    protected static string $resource = DetailSuratJalanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
