<?php

namespace App\Filament\Resources\IdentitasTokos;

use App\Filament\Resources\IdentitasTokos\Pages\CreateIdentitasToko;
use App\Filament\Resources\IdentitasTokos\Pages\EditIdentitasToko;
use App\Filament\Resources\IdentitasTokos\Pages\ListIdentitasTokos;
use App\Filament\Resources\IdentitasTokos\Pages\ViewIdentitasToko;
use App\Filament\Resources\IdentitasTokos\Schemas\IdentitasTokoForm;
use App\Filament\Resources\IdentitasTokos\Schemas\IdentitasTokoInfolist;
use App\Filament\Resources\IdentitasTokos\Tables\IdentitasTokosTable;
use App\Models\IdentitasToko;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IdentitasTokoResource extends Resource
{
    protected static ?string $model = IdentitasToko::class;

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Master Data';
    protected static ?string $recordTitleAttribute = 'nama_toko';

    public static function form(Schema $schema): Schema
    {
        return IdentitasTokoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return IdentitasTokoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IdentitasTokosTable::configure($table);
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
            'index' => ListIdentitasTokos::route('/'),
            'create' => CreateIdentitasToko::route('/create'),
            'view' => ViewIdentitasToko::route('/{record}'),
            'edit' => EditIdentitasToko::route('/{record}/edit'),
        ];
    }
}
