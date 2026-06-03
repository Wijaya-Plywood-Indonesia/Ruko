<?php

namespace App\Filament\Resources\Ayams\Pages;

use App\Filament\Resources\Ayams\AyamResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAyam extends ViewRecord
{
    protected static string $resource = AyamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
