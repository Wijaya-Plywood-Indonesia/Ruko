<?php

namespace App\Filament\Resources\Kandangs;

use App\Filament\Resources\Kandangs\Pages\CreateKandang;
use App\Filament\Resources\Kandangs\Pages\EditKandang;
use App\Filament\Resources\Kandangs\Pages\ListKandangs;
use App\Filament\Resources\Kandangs\Pages\ViewKandang;
use App\Filament\Resources\Kandangs\RelationManagers\AyamRelationManager;
use App\Filament\Resources\Kandangs\Schemas\KandangForm;
use App\Filament\Resources\Kandangs\Schemas\KandangInfolist;
use App\Filament\Resources\Kandangs\Tables\KandangsTable;
use App\Models\Kandang;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KandangResource extends Resource
{
    protected static ?string $model = Kandang::class;

    protected static string|UnitEnum|null $navigationGroup = 'Produksi & Kandang';

    protected static ?string $recordTitleAttribute = 'Kandang';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return KandangForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KandangInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KandangsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AyamRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKandangs::route('/'),
            'create' => CreateKandang::route('/create'),
            'view' => ViewKandang::route('/{record}'),
            'edit' => EditKandang::route('/{record}/edit'),
        ];
    }
}
