<?php

namespace App\Filament\Resources\Pembelis;

use App\Filament\Resources\Pembelis\Pages\CreatePembeli;
use App\Filament\Resources\Pembelis\Pages\EditPembeli;
use App\Filament\Resources\Pembelis\Pages\ListPembelis;
use App\Filament\Resources\Pembelis\Pages\ViewPembeli;
use App\Filament\Resources\Pembelis\RelationManagers\RekeningPembeliRelationManager;
use App\Filament\Resources\Pembelis\Schemas\PembeliForm;
use App\Filament\Resources\Pembelis\Schemas\PembeliInfolist;
use App\Filament\Resources\Pembelis\Tables\PembelisTable;
use App\Models\Pembeli;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PembeliResource extends Resource
{
    protected static ?string $model = Pembeli::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Data Pembeli';
    protected static ?string $pluralModelLabel = 'Data Pembeli';
    protected static ?string $modelLabel = 'Data Pembeli';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return PembeliForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PembeliInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PembelisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
                //
            RekeningPembeliRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPembelis::route('/'),
            'create' => CreatePembeli::route('/create'),
            'view' => ViewPembeli::route('/{record}'),
            'edit' => EditPembeli::route('/{record}/edit'),
        ];
    }
}
