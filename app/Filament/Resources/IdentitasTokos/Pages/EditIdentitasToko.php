<?php

namespace App\Filament\Resources\IdentitasTokos\Pages;

use App\Filament\Resources\IdentitasTokos\IdentitasTokoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditIdentitasToko extends EditRecord
{
    protected static string $resource = IdentitasTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
