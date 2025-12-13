<?php

namespace App\Filament\Resources\Pembelis\Pages;

use App\Filament\Resources\Pembelis\PembeliResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPembeli extends EditRecord
{
    protected static string $resource = PembeliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
