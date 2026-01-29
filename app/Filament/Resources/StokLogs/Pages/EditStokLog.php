<?php

namespace App\Filament\Resources\StokLogs\Pages;

use App\Filament\Resources\StokLogs\StokLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStokLog extends EditRecord
{
    protected static string $resource = StokLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
