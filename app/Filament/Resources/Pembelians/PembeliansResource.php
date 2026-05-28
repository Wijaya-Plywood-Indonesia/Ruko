<?php

namespace App\Filament\Resources\Pembelians;

use App\Filament\Resources\Pembelians\Pages\CreatePembelians;
use App\Filament\Resources\Pembelians\Pages\EditPembelians;
use App\Filament\Resources\Pembelians\Pages\ListPembelians;
use App\Filament\Resources\Pembelians\Pages\ViewPembelians;
use App\Filament\Resources\Pembelians\RelationManagers\DetailPembeliansRelationManager;
use App\Filament\Resources\Pembelians\RelationManagers\MetodePembayaransRelationManager;
use App\Filament\Resources\Pembelians\Schemas\PembeliansForm;
use App\Filament\Resources\Pembelians\Schemas\PembeliansInfolist;
use App\Filament\Resources\Pembelians\Tables\PembeliansTable;
use App\Models\Pembelian;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PembeliansResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    public static function form(Schema $schema): Schema
    {
        return PembeliansForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PembeliansInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PembeliansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DetailPembeliansRelationManager::class,
            MetodePembayaransRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPembelians::route('/'),
            'create' => Pages\Pembelian::route('/create'),
            'view' => ViewPembelians::route('/{record}'),
            'edit' => EditPembelians::route('/{record}/edit'),
        ];
    }
}
