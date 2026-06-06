<?php

namespace App\Filament\Resources\ListAkuns;

use App\Filament\Resources\ListAkuns\Pages\CreateListAkun;
use App\Filament\Resources\ListAkuns\Pages\EditListAkun;
use App\Filament\Resources\ListAkuns\Pages\ListListAkuns;
use App\Filament\Resources\ListAkuns\Schemas\ListAkunForm;
use App\Filament\Resources\ListAkuns\Tables\ListAkunsTable;
use App\Models\ListAkun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ListAkunResource extends Resource
{
    protected static ?string $model = ListAkun::class;

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'List Akun';
    protected static ?string $pluralModelLabel = 'List Akun';
    protected static ?string $modelLabel = 'List Aku';
    protected static string|UnitEnum|null $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return ListAkunForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ListAkunsTable::configure($table);
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
            'index' => ListListAkuns::route('/'),
            'create' => CreateListAkun::route('/create'),
            'edit' => EditListAkun::route('/{record}/edit'),
        ];
    }
}
