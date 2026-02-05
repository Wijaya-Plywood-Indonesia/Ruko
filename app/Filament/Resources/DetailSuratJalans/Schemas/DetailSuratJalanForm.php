<?php

namespace App\Filament\Resources\DetailSuratJalans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;

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
                    ->preload()
                    ->required()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->disabled(function ($livewire) {
                        if (method_exists($livewire, 'getOwnerRecord')) {
                            return $livewire->getOwnerRecord()->status !== 'draft';
                        }

                        return false;
                    }),


                TextInput::make('qty_kirim')
                    ->label("Jumlah Barang")
                    ->required()
                    ->numeric()
                    ->disabled(function ($livewire) {
                        if (method_exists($livewire, 'getOwnerRecord')) {
                            return $livewire->getOwnerRecord()->status !== 'draft';
                        }
                        return false;
                    }),

                Textarea::make('catatan')
                    ->columnSpanFull()
                    ->disabled(function ($livewire) {
                        if (method_exists($livewire, 'getOwnerRecord')) {
                            return $livewire->getOwnerRecord()->status !== 'draft';
                        }
                        return false;
                    }),
            ]);
    }
}
