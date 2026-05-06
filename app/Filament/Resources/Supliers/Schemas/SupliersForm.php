<?php

namespace App\Filament\Resources\Supliers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SupliersForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                TextInput::make('nama')
                    ->label('Nama Supplier')
                    ->required()
                    ->maxLength(255),

                TextInput::make('telepon')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(50),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),

                TextInput::make('npwp')
                    ->label('NPWP')
                    ->maxLength(100),

                Textarea::make('alamat')
                    ->label('Alamat')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('keterangan_tambahan')
                    ->label('Keterangan Tambahan')
                    ->rows(4)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),

            ]);
    }
}
