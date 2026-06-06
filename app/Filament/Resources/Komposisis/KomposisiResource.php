<?php

namespace App\Filament\Resources\Komposisis;

use App\Filament\Resources\Komposisis\Pages\CreateKomposisi;
use App\Filament\Resources\Komposisis\Pages\EditKomposisi;
use App\Filament\Resources\Komposisis\Pages\ListKomposisis;
use App\Filament\Resources\Komposisis\Pages\ViewKomposisi;
use App\Filament\Resources\Komposisis\RelationManagers\DetailKomposisiRelationManager;
use App\Filament\Resources\Komposisis\Schemas\KomposisiForm;
use App\Filament\Resources\Komposisis\Schemas\KomposisiInfolist;
use App\Filament\Resources\Komposisis\Tables\KomposisisTable;
use App\Models\Komposisi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class KomposisiResource extends Resource
{
    protected static ?string $model = Komposisi::class;


    protected static string|UnitEnum|null $navigationGroup = 'Produksi & Kandang';

    protected static ?string $recordTitleAttribute = 'Komposisi';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return KomposisiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KomposisiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KomposisisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DetailKomposisiRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKomposisis::route('/'),
            'create' => CreateKomposisi::route('/create'),
            'view' => ViewKomposisi::route('/{record}'),
            'edit' => EditKomposisi::route('/{record}/edit'),
        ];
    }
}
