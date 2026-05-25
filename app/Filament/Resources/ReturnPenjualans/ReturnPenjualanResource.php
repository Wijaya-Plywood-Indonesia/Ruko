<?php

namespace App\Filament\Resources\ReturnPenjualans;

use App\Filament\Resources\ReturnPenjualans\Pages\CreateReturnPenjualan;
use App\Filament\Resources\ReturnPenjualans\Pages\EditReturnPenjualan;
use App\Filament\Resources\ReturnPenjualans\Pages\FormReturnPenjualan;
use App\Filament\Resources\ReturnPenjualans\Pages\ListReturnPenjualans;
use App\Filament\Resources\ReturnPenjualans\Schemas\ReturnPenjualanForm;
use App\Filament\Resources\ReturnPenjualans\Tables\ReturnPenjualansTable;
use App\Models\ReturnPenjualan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ReturnPenjualanResource extends Resource
{
    protected static ?string $model = ReturnPenjualan::class;

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $recordTitleAttribute = 'ReturnPenjualan';

    public static function form(Schema $schema): Schema
    {
        return ReturnPenjualanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReturnPenjualansTable::configure($table);
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
            'index' => ListReturnPenjualans::route('/'),
            'form-return-penjualan' => FormReturnPenjualan::route('/form'),
            'create' => CreateReturnPenjualan::route('/create'),
            'edit' => EditReturnPenjualan::route('/{record}/edit'),
        ];
    }
}
