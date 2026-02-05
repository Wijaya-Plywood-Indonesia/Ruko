<?php

namespace App\Filament\Resources\SuratJalans\Pages;

use App\Filament\Resources\SuratJalans\SuratJalanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSuratJalan extends EditRecord
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
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn($record) => $this->bolehEdit($record))
                ->authorize(fn($record) => $this->bolehEdit($record)),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', [
            'record' => $this->record,
        ]);
    }

}
