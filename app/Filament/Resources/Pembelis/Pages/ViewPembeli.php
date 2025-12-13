<?php

namespace App\Filament\Resources\Pembelis\Pages;

use App\Filament\Resources\Pembelis\PembeliResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPembeli extends ViewRecord
{
    protected static string $resource = PembeliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
