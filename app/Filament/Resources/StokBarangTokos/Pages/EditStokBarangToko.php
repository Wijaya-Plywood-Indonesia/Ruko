<?php

namespace App\Filament\Resources\StokBarangTokos\Pages;

use App\Filament\Resources\StokBarangTokos\StokBarangTokoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStokBarangToko extends EditRecord
{
    protected static string $resource = StokBarangTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
