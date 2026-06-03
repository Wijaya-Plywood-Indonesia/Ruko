<?php

namespace App\Filament\Resources\Ayams;

use App\Filament\Resources\Ayams\Pages\CreateAyam;
use App\Filament\Resources\Ayams\Pages\EditAyam;
use App\Filament\Resources\Ayams\Pages\ListAyams;
use App\Filament\Resources\Ayams\Pages\ViewAyam;
use App\Filament\Resources\Ayams\Schemas\AyamForm;
use App\Filament\Resources\Ayams\Schemas\AyamInfolist;
use App\Filament\Resources\Ayams\Tables\AyamsTable;
use App\Models\Ayam;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AyamResource extends Resource
{
    protected static ?string $model = Ayam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Ayam';

    public static function form(Schema $schema): Schema
    {
        return AyamForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AyamInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AyamsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAyams::route('/'),
            'create' => CreateAyam::route('/create'),
            'view' => ViewAyam::route('/{record}'),
            'edit' => EditAyam::route('/{record}/edit'),
        ];
    }
}
