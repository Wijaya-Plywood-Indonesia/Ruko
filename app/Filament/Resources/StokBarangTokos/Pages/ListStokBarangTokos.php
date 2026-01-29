<?php

namespace App\Filament\Resources\StokBarangTokos\Pages;

use App\Filament\Resources\StokBarangTokos\StokBarangTokoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStokBarangTokos extends ListRecords
{
    protected static string $resource = StokBarangTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
