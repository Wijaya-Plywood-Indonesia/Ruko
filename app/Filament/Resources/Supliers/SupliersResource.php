<?php

namespace App\Filament\Resources\Supliers;

use App\Filament\Resources\Supliers\Pages\CreateSupliers;
use App\Filament\Resources\Supliers\Pages\EditSupliers;
use App\Filament\Resources\Supliers\Pages\ListSupliers;
use App\Filament\Resources\Supliers\Pages\ViewSupliers;
use App\Filament\Resources\Supliers\Schemas\SupliersForm;
use App\Filament\Resources\Supliers\Schemas\SupliersInfolist;
use App\Filament\Resources\Supliers\Tables\SupliersTable;
use App\Models\Supplier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SupliersResource extends Resource
{
    protected static ?string $model = Supplier::class;

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return SupliersForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupliersInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupliersTable::configure($table);
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
            'index' => ListSupliers::route('/'),
            'create' => CreateSupliers::route('/create'),
            'view' => ViewSupliers::route('/{record}'),
            'edit' => EditSupliers::route('/{record}/edit'),
        ];
    }
}
