<?php

namespace App\Filament\Resources\Kandangs\Pages;

use App\Filament\Resources\Kandangs\KandangResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKandang extends ViewRecord
{
    protected static string $resource = KandangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
