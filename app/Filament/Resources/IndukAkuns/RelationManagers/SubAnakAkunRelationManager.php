<?php

namespace App\Filament\Resources\IndukAkuns\RelationManagers;

use App\Filament\Resources\SubAnakAkuns\SubAnakAkunResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class SubAnakAkunRelationManager extends RelationManager
{
    protected static string $relationship = 'subAnakAkun';

    protected static ?string $relatedResource = SubAnakAkunResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
