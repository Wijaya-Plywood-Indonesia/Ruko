<?php

namespace App\Filament\Resources\ListAkuns\Pages;

use App\Filament\Resources\ListAkuns\ListAkunResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditListAkun extends EditRecord
{
    protected static string $resource = ListAkunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
