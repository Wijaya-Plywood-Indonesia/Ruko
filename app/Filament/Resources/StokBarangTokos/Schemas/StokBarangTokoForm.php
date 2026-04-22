<?php

namespace App\Filament\Resources\StokBarangTokos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StokBarangTokoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('barang_id')
                    ->label('Barang')
                    ->relationship('barang', 'nama_barang')
                    ->searchable()
                    ->required(),

                Select::make('toko_id')
                    ->label('Toko')
                    ->relationship('toko', 'nama_toko')
                    ->searchable()
                    ->required(),

                TextInput::make('stok')
                    ->label('Stok')
                    ->numeric()
                    ->default(0)
            ]);
    }
}