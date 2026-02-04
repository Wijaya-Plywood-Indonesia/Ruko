<?php

namespace App\Filament\Resources\StokBarangTokos;

use App\Filament\Resources\StokBarangTokos\Pages\CreateStokBarangToko;
use App\Filament\Resources\StokBarangTokos\Pages\EditStokBarangToko;
use App\Filament\Resources\StokBarangTokos\Pages\ListStokBarangTokos;
use App\Filament\Resources\StokBarangTokos\Schemas\StokBarangTokoForm;
use App\Filament\Resources\StokBarangTokos\Tables\StokBarangTokosTable;
use App\Models\StokBarangToko;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StokBarangTokoResource extends Resource
{
    protected static ?string $model = StokBarangToko::class;
    protected static string|UnitEnum|null $navigationGroup = 'Stock Barang';
    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return StokBarangTokoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StokBarangTokosTable::configure($table);
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
            'index' => ListStokBarangTokos::route('/'),
            'create' => CreateStokBarangToko::route('/create'),
            'edit' => EditStokBarangToko::route('/{record}/edit'),
        ];
    }
}
