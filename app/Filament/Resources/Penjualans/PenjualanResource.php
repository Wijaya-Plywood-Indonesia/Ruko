<?php

namespace App\Filament\Resources\Penjualans;

use App\Filament\Resources\Penjualans\Pages\CreatePenjualan;
use App\Filament\Resources\Penjualans\Pages\DownloadExcel;
use App\Filament\Resources\Penjualans\Pages\EditPenjualan;
use App\Filament\Resources\Penjualans\Pages\ListPenjualans;
use App\Filament\Resources\Penjualans\Pages\PosPenjualan;
use App\Filament\Resources\Penjualans\Pages\PreviewExport;
use App\Filament\Resources\Penjualans\Pages\Settings;
use App\Filament\Resources\Penjualans\Pages\ViewPenjualan;
use App\Filament\Resources\Penjualans\RelationManagers\DetailsRelationManager;
use App\Filament\Resources\Penjualans\Schemas\PenjualanForm;
use App\Filament\Resources\Penjualans\Schemas\PenjualanInfolist;
use App\Filament\Resources\Penjualans\Tables\PenjualansTable;
use App\Models\Penjualan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PenjualanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    public static function form(Schema $schema): Schema
    {
        return PenjualanForm::configure($schema);
    }





    public static function infolist(Schema $schema): Schema
    {
        return PenjualanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PenjualansTable::configure($table);
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
            'index' => ListPenjualans::route('/'),
            'settings' => Settings::route('/settings'),
            'preview' => PreviewExport::route('/preview'),
            // 'product' => PreviewExport::route('/preview'),
            'pos' => PosPenjualan::route('/pos'),
            'create' => CreatePenjualan::route('/create'),
            'download' => DownloadExcel::route('/download'),
            'view' => ViewPenjualan::route('/{record}'),
            'edit' => EditPenjualan::route('/{record}/edit'),

        ];
    }
}
