<?php

namespace App\Filament\Resources\IdentitasTokos\Pages;

use App\Filament\Resources\IdentitasTokos\IdentitasTokoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIdentitasTokos extends ListRecords
{
    protected static string $resource = IdentitasTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
