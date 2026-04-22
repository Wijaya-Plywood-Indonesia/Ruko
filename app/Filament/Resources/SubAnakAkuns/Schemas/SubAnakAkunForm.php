<?php

namespace App\Filament\Resources\SubAnakAkuns\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SubAnakAkunForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('id_anak_akun')
                    ->relationship('anakAkun', 'nama_anak_akun')
                    ->searchable()
                    ->required(),

                TextInput::make('kode_sub_anak_akun')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('nama_sub_anak_akun')
                    ->required(),

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
                    ->default('aktif')
                    ->required(),

                Textarea::make('keterangan')
                    ->columnSpanFull(),
            ]);
    }
}
