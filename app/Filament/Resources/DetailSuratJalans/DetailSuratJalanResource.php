<?php

namespace App\Filament\Resources\DetailSuratJalans;

use App\Filament\Resources\DetailSuratJalans\Pages\CreateDetailSuratJalan;
use App\Filament\Resources\DetailSuratJalans\Pages\EditDetailSuratJalan;
use App\Filament\Resources\DetailSuratJalans\Pages\ListDetailSuratJalans;
use App\Filament\Resources\DetailSuratJalans\Schemas\DetailSuratJalanForm;
use App\Filament\Resources\DetailSuratJalans\Tables\DetailSuratJalansTable;
use App\Models\DetailSuratJalan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DetailSuratJalanResource extends Resource
{
    protected static ?string $model = DetailSuratJalan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return DetailSuratJalanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DetailSuratJalansTable::configure($table);
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
            'index' => ListDetailSuratJalans::route('/'),
            'create' => CreateDetailSuratJalan::route('/create'),
            'edit' => EditDetailSuratJalan::route('/{record}/edit'),
        ];
    }
}
