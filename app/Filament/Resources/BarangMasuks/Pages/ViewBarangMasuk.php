<?php

namespace App\Filament\Resources\BarangMasuks\Pages;

use App\Filament\Resources\BarangMasuks\BarangMasukResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBarangMasuk extends ViewRecord
{
    protected static string $resource = BarangMasukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
