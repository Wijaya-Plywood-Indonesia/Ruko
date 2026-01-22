<?php

namespace App\Filament\Resources\SuratJalans\RelationManagers;

use App\Filament\Resources\DetailSuratJalans\DetailSuratJalanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    protected static ?string $relatedResource = DetailSuratJalanResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
