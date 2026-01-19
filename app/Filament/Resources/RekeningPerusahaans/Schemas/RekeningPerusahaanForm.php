<?php

namespace App\Filament\Resources\RekeningPerusahaans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RekeningPerusahaanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                TextInput::make('pemilik_rekening')
                    ->label('Pemilik Rekening')
                    ->maxLength(255),

                TextInput::make('nama_bank')
                    ->label('Nama Bank')
                    ->placeholder('BCA, BRI, Mandiri, OVO, DANA')
                    ->maxLength(255),

                TextInput::make('no_rekening')
                    ->label('Nomor Rekening / E-Wallet')
                    ->maxLength(255),

                TextInput::make('atas_nama')
                    ->label('Atas Nama')
                    ->maxLength(255),
            ]);
    }
}
