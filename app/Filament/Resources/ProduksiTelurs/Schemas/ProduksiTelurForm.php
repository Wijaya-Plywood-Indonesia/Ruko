<?php

namespace App\Filament\Resources\ProduksiTelurs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ProduksiTelurForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id_kandang')
                    ->required()
                    ->numeric(),
                TextInput::make('id_ayam')
                    ->required()
                    ->numeric(),
                DatePicker::make('tanggal')
                    ->required(),
                TextInput::make('jumlah_telur_butir')
                    ->tel()
                    ->required()
                    ->numeric(),
                TextInput::make('jumlah_telur_retak')
                    ->tel()
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('jumlah_telur_pecah')
                    ->tel()
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('jumlah_ayam_mati')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('hen_day_production')
                    ->numeric(),
                TextInput::make('created_by'),
                TextInput::make('validated_by'),
                DateTimePicker::make('validated_at'),
                Textarea::make('keterangan')
                    ->columnSpanFull(),
            ]);
    }
}
