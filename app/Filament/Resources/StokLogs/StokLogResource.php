<?php

namespace App\Filament\Resources\StokLogs;

use App\Filament\Resources\StokLogs\Pages\CreateStokLog;
use App\Filament\Resources\StokLogs\Pages\EditStokLog;
use App\Filament\Resources\StokLogs\Pages\ListStokLogs;
use App\Filament\Resources\StokLogs\Schemas\StokLogForm;
use App\Filament\Resources\StokLogs\Tables\StokLogsTable;
use App\Models\StokLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StokLogResource extends Resource
{
    protected static ?string $model = StokLog::class;

    //  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Stock Barang';
    protected static ?string $recordTitleAttribute = 'toko_id';

    public static function form(Schema $schema): Schema
    {
        return StokLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StokLogsTable::configure($table);
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
            'index' => ListStokLogs::route('/'),
            'create' => CreateStokLog::route('/create'),
            'edit' => EditStokLog::route('/{record}/edit'),
        ];
    }
}
