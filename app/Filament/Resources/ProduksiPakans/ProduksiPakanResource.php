<?php

namespace App\Filament\Resources\ProduksiPakans;

use App\Filament\Resources\ProduksiPakans\Pages\CreateProduksiPakan;
use App\Filament\Resources\ProduksiPakans\Pages\EditProduksiPakan;
use App\Filament\Resources\ProduksiPakans\Pages\ListProduksiPakans;
use App\Filament\Resources\ProduksiPakans\Pages\ViewProduksiPakan;
use App\Filament\Resources\ProduksiPakans\RelationManagers\ProduksiPakanBahanRelationManager;
use App\Filament\Resources\ProduksiPakans\RelationManagers\ProduksiPakanHasilRelationManager;
use App\Filament\Resources\ProduksiPakans\Schemas\ProduksiPakanForm;
use App\Filament\Resources\ProduksiPakans\Schemas\ProduksiPakanInfolist;
use App\Filament\Resources\ProduksiPakans\Tables\ProduksiPakansTable;
use App\Models\ProduksiPakan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProduksiPakanResource extends Resource
{
    protected static ?string $model = ProduksiPakan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'ProduksiPakan';

    public static function form(Schema $schema): Schema
    {
        return ProduksiPakanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProduksiPakanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProduksiPakansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProduksiPakanBahanRelationManager::class,
            ProduksiPakanHasilRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProduksiPakans::route('/'),
            'create' => CreateProduksiPakan::route('/create'),
            'view' => ViewProduksiPakan::route('/{record}'),
            'edit' => EditProduksiPakan::route('/{record}/edit'),
        ];
    }
}
