<?php

namespace App\Filament\Resources\JurnalPembantuHeaders\Pages;

use App\Filament\Resources\JurnalPembantuHeaders\JurnalPembantuHeaderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditJurnalPembantuHeader extends EditRecord
{
    protected static string $resource = JurnalPembantuHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
