<?php

namespace App\Filament\Resources\RekeningPerusahaans\Pages;

use App\Filament\Resources\RekeningPerusahaans\RekeningPerusahaanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRekeningPerusahaan extends ViewRecord
{
    protected static string $resource = RekeningPerusahaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
