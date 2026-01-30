<?php

namespace App\Filament\Resources\RekeningPerusahaans;

use App\Filament\Resources\RekeningPerusahaans\Pages\CreateRekeningPerusahaan;
use App\Filament\Resources\RekeningPerusahaans\Pages\EditRekeningPerusahaan;
use App\Filament\Resources\RekeningPerusahaans\Pages\ListRekeningPerusahaans;
use App\Filament\Resources\RekeningPerusahaans\Pages\ViewRekeningPerusahaan;
use App\Filament\Resources\RekeningPerusahaans\Schemas\RekeningPerusahaanForm;
use App\Filament\Resources\RekeningPerusahaans\Schemas\RekeningPerusahaanInfolist;
use App\Filament\Resources\RekeningPerusahaans\Tables\RekeningPerusahaansTable;
use App\Models\RekeningPerusahaan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RekeningPerusahaanResource extends Resource
{
    protected static ?string $model = RekeningPerusahaan::class;

    //  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;
    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Rekening Perusahaan';
    protected static ?string $pluralModelLabel = 'Rekening Perusahaan';
    protected static ?string $modelLabel = 'Rekening Perusahaan';
    protected static ?string $recordTitleAttribute = 'nama_pemilik';


    public static function form(Schema $schema): Schema
    {
        return RekeningPerusahaanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RekeningPerusahaanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RekeningPerusahaansTable::configure($table);
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
            'index' => ListRekeningPerusahaans::route('/'),
            'create' => CreateRekeningPerusahaan::route('/create'),
            'view' => ViewRekeningPerusahaan::route('/{record}'),
            'edit' => EditRekeningPerusahaan::route('/{record}/edit'),
        ];
    }
}
