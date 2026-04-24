<?php

namespace App\Filament\Resources\BarangMasuks;

use App\Filament\Resources\BarangMasuks\Pages\CreateBarangMasuk;
use App\Filament\Resources\BarangMasuks\Pages\EditBarangMasuk;
use App\Filament\Resources\BarangMasuks\Pages\ListBarangMasuks;
use App\Filament\Resources\BarangMasuks\Pages\ViewBarangMasuk;
use App\Filament\Resources\BarangMasuks\RelationManagers\DetailBarangMasukRelationManager;
use App\Filament\Resources\BarangMasuks\Schemas\BarangMasukForm;
use App\Filament\Resources\BarangMasuks\Schemas\BarangMasukInfolist;
use App\Filament\Resources\BarangMasuks\Tables\BarangMasuksTable;
use App\Models\BarangMasuk;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BarangMasukResource extends Resource
{
    protected static ?string $model = BarangMasuk::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'BarangMasuk';

    public static function form(Schema $schema): Schema
    {
        return BarangMasukForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BarangMasukInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BarangMasuksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DetailBarangMasukRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBarangMasuks::route('/'),
            'create' => CreateBarangMasuk::route('/create'),
            'view' => ViewBarangMasuk::route('/{record}'),
            'edit' => EditBarangMasuk::route('/{record}/edit'),
        ];
    }
}
