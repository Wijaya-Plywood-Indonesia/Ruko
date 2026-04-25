<?php

namespace App\Filament\Resources\Komposisis\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KomposisiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('id_barang')
                    ->label('Pilih Barang')
                    ->relationship('barang', 'nama_barang') // Mengasumsikan tabel barang punya kolom 'nama'
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}
