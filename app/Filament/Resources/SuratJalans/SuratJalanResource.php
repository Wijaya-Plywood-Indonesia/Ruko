<?php

namespace App\Filament\Resources\SuratJalans;

use App\Filament\Resources\SuratJalans\RelationManagers\DetailsRelationManager;
use App\Filament\Resources\SuratJalans\Pages\CreateSuratJalan;
use App\Filament\Resources\SuratJalans\Pages\EditSuratJalan;
use App\Filament\Resources\SuratJalans\Pages\ListSuratJalans;
use App\Filament\Resources\SuratJalans\Pages\ViewSuratJalan;
use App\Filament\Resources\SuratJalans\Schemas\SuratJalanForm;
use App\Filament\Resources\SuratJalans\Schemas\SuratJalanInfolist;
use App\Filament\Resources\SuratJalans\Tables\SuratJalansTable;
use App\Models\SuratJalan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;


class SuratJalanResource extends Resource
{
    protected static ?string $model = SuratJalan::class;

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Stock Barang';

    protected static ?string $recordTitleAttribute = 'no_surat_jalan';

    public static function form(Schema $schema): Schema
    {
        return SuratJalanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SuratJalanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuratJalansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
                //
            DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuratJalans::route('/'),
            'create' => CreateSuratJalan::route('/create'),
            'view' => ViewSuratJalan::route('/{record}'),
            'edit' => EditSuratJalan::route('/{record}/edit'),
        ];
    }
}
