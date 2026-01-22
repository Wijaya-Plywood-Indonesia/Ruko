<?php

namespace App\Filament\Resources\DetailSuratJalans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DetailSuratJalanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('barang_id')
                    ->label('Barang')
                    ->relationship('barang', 'nama_barang')
                    ->searchable()
                    ->required()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->disabled(fn() => $this->getOwnerRecord()->status !== 'draft'),

                TextInput::make('qty_kirim')
                    ->label("Jumlah Barang")
                    ->required()
                    ->numeric(),
                Textarea::make('catatan')
                    ->columnSpanFull(),
            ]);
    }
}
