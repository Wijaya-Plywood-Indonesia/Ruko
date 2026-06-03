<?php

namespace App\Filament\Resources\Ayams\Schemas;

use App\Models\Kandang;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AyamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('id_kandang')
                    ->label("Kandang")
                    ->options(
                        Kandang::where('is_aktif', true)
                            ->pluck('nama_kandang', 'id')
                    )
                    ->searchable()
                    ->required(),
                TextInput::make('nama_batch'),
                DatePicker::make('tanggal_masuk')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->maxDate(now())
                    ->default(now())
                    ->live()
                    ->closeOnDateSelection()
                    ->suffixIcon('heroicon-o-calendar')
                    ->suffixIconColor('primary')
                    ->required(),
                TextInput::make('jumlah_awal')
                    ->label('Jumlah Ayam Masuk')
                    ->required()
                    ->numeric(),
                TextInput::make('usia')
                    ->label('Usia ayam')
                    ->numeric()
                    ->default(1)
                    ->suffix('hari')
                    ->required(),
                Textarea::make('keterangan'),
            ]);
    }
}
