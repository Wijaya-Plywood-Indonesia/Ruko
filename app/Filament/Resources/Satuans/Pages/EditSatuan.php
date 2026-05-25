<?php

namespace App\Filament\Resources\Satuans\Pages;

use App\Filament\Resources\Satuans\SatuanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSatuan extends EditRecord
{
    protected static string $resource = SatuanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // /**
    //  * @return array<class-string>
    //  */
    // public function getRelationManagers(): array
    // {
    //     return [
    //         \App\Filament\Resources\Satuans\RelationManagers\SatuanKonversiRelationManager::class,
    //     ];
    // }
}
