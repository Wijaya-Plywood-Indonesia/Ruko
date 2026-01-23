<?php

namespace App\Filament\Resources\SuratJalans\RelationManagers;

use App\Filament\Resources\DetailSuratJalans\DetailSuratJalanResource;
use App\Filament\Resources\DetailSuratJalans\Schemas\DetailSuratJalanForm;
use App\Filament\Resources\DetailSuratJalans\Tables\DetailSuratJalansTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return DetailSuratJalanForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return DetailSuratJalansTable::configure($table);
    }

}
