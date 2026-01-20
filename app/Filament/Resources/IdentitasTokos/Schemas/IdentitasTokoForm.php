<?php

namespace App\Filament\Resources\IdentitasTokos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IdentitasTokoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                TextInput::make('kode_toko')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('nama_toko')
                    ->required()
                    ->maxLength(255),
                TextInput::make('pemilik')
                    ->maxLength(255),
                Textarea::make('alamat')
                    ->rows(3),
                TextInput::make('telepon')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Select::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                    ])
                    ->default('aktif')
                    ->required(),
                Textarea::make('keterangan')
                    ->rows(2),

            ]);
    }
}
