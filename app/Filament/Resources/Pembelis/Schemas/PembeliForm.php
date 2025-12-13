<?php

namespace App\Filament\Resources\Pembelis\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PembeliForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // =================================
                // SECTION IDENTITAS PEMBELI
                // =================================
                Section::make('Identitas Pembeli')
                    ->description('Isi data umum pembeli.')
                    ->schema([

                        TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('alamat')
                            ->label('Alamat')
                            ->required()
                            ->rows(3),

                        TextInput::make('telepon')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(20)
                            ->nullable(),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->nullable(),

                    ])
                    ->icon('heroicon-o-user')
                // ->columns(2)
                ,
            ]);
    }
}
