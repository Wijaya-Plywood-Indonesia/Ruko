<?php

namespace App\Filament\Resources\Barangs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BarangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode_barang')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),

                TextInput::make('barcode')
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),

                TextInput::make('nama_barang')
                    ->required()
                    ->maxLength(255),

                Select::make('id_kategori')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama_kategori')
                    ->searchable()
                    ->required(),

                Select::make('id_satuan')
                    ->label('Satuan')
                    ->relationship('satuan', 'nama_satuan')
                    ->required(),

                TextInput::make('harga_beli')
                    ->label('HPP')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                TextInput::make('harga_jual')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                TextInput::make('stok_minimum')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
