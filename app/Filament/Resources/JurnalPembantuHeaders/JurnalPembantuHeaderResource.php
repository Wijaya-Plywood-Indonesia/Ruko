<?php

namespace App\Filament\Resources\JurnalPembantuHeaders;

use App\Filament\Resources\JurnalPembantuHeaders\Pages\CreateJurnalPembantuHeader;
use App\Filament\Resources\JurnalPembantuHeaders\Pages\EditJurnalPembantuHeader;
use App\Filament\Resources\JurnalPembantuHeaders\Pages\ListJurnalPembantuHeaders;
use App\Filament\Resources\JurnalPembantuHeaders\Pages\ViewJurnalPembantuHeader;
use App\Filament\Resources\JurnalPembantuHeaders\RelationManagers\JurnalPembantuItemRelationManager;
use App\Filament\Resources\JurnalPembantuHeaders\Schemas\JurnalPembantuHeaderForm;
use App\Filament\Resources\JurnalPembantuHeaders\Schemas\JurnalPembantuHeaderInfolist;
use App\Filament\Resources\JurnalPembantuHeaders\Tables\JurnalPembantuHeadersTable;
use App\Models\JurnalPembantuHeader;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class JurnalPembantuHeaderResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Akuntansi';
    protected static ?string $model = JurnalPembantuHeader::class;
    protected static ?int $navigationSort = 3;

    //  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'no_jurnal_pembantu';

    public static function form(Schema $schema): Schema
    {
        return JurnalPembantuHeaderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return JurnalPembantuHeaderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JurnalPembantuHeadersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            JurnalPembantuItemRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJurnalPembantuHeaders::route('/'),
            'create' => CreateJurnalPembantuHeader::route('/create'),
            'view' => ViewJurnalPembantuHeader::route('/{record}'),
            'edit' => EditJurnalPembantuHeader::route('/{record}/edit'),
        ];
    }
}
