<?php

namespace App\Filament\Resources\SuratJalans\Pages;

use App\Filament\Resources\SuratJalans\SuratJalanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSuratJalan extends ViewRecord
{
    protected static string $resource = SuratJalanResource::class;
    protected function bolehEdit($record): bool
    {
        return auth()->user()->hasRole('super_admin')
            || $record->status === 'draft';
    }
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn($record) => $this->bolehEdit($record))
                ->authorize(fn($record) => $this->bolehEdit($record)),
        ];
    }
}
