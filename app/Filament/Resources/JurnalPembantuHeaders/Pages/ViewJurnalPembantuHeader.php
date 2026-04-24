<?php

namespace App\Filament\Resources\JurnalPembantuHeaders\Pages;

use App\Filament\Resources\JurnalPembantuHeaders\JurnalPembantuHeaderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewJurnalPembantuHeader extends ViewRecord
{
    protected static string $resource = JurnalPembantuHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
