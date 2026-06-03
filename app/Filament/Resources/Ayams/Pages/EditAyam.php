<?php

namespace App\Filament\Resources\Ayams\Pages;

use App\Filament\Resources\Ayams\AyamResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAyam extends EditRecord
{
    protected static string $resource = AyamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
