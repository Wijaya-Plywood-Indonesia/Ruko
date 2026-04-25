<?php

namespace App\Filament\Resources\AnakAkuns\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AnakAkunForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('id_induk_akun')
                    ->relationship('indukAkun', 'nama_induk_akun')
                    ->preload()
                    ->searchable()
                    ->required(),

                TextInput::make('kode_anak_akun')
                    ->required(),

                TextInput::make('nama_anak_akun')
                    ->required(),

                // Select::make('parent')
                //     ->relationship('parentAkun', 'nama_anak_akun')
                //     ->searchable()
                //     ->preload()
                //     ->placeholder('Tanpa Parent'),

                Select::make('saldo_normal')
                    ->options([
                        'debet' => 'Debet',
                        'kredit' => 'Kredit',
                    ])
                    ->required(),

                Select::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                    ])

                    ->required(),

                Textarea::make('keterangan')
                    ->columnSpanFull(),

            ]);
    }
}
