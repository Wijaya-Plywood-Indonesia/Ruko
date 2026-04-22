<?php

namespace App\Filament\Resources\IndukAkuns\RelationManagers;

use App\Filament\Resources\AnakAkuns\AnakAkunResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class AnakAkunsRelationManager extends RelationManager
{
    public function isReadOnly(): bool
    {
        return false;
    }
    protected static string $relationship = 'anakAkuns';

    protected static ?string $relatedResource = AnakAkunResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
