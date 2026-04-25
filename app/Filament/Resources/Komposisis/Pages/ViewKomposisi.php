<?php

namespace App\Filament\Resources\Komposisis\Pages;

use App\Filament\Resources\Komposisis\KomposisiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKomposisi extends ViewRecord
{
    protected static string $resource = KomposisiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
