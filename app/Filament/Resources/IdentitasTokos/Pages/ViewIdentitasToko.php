<?php

namespace App\Filament\Resources\IdentitasTokos\Pages;

use App\Filament\Resources\IdentitasTokos\IdentitasTokoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIdentitasToko extends ViewRecord
{
    protected static string $resource = IdentitasTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
