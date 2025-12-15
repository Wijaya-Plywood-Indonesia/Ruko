<?php

namespace App\Filament\Resources\Penjualans\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PenjualanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no_nota')
                    ->label('No Nota')
                    ->default(fn() => 'INV-' . now()->format('YmdHis'))
                    ->disabled()
                    ->dehydrated()
                    ->required(),

                DateTimePicker::make('tanggal')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),

                TextInput::make('nama_customer')
                    ->label('Nama Customer')
                    ->required()
                    ->maxLength(255),

                Textarea::make('alamat')
                    ->label('Alamat')
                    ->columnSpanFull(),

                Select::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->options([
                        'TUNAI' => 'Tunai',
                        'TRANSFER' => 'Transfer',
                    ])
                    ->reactive()
                    ->required(),

                TextInput::make('bank')
                    ->label('Bank')
                    ->visible(fn($get) => $get('metode_pembayaran') === 'TRANSFER'),

                TextInput::make('no_rekening')
                    ->label('No Rekening')
                    ->visible(fn($get) => $get('metode_pembayaran') === 'TRANSFER'),

                TextInput::make('kendaraan')
                    ->label('Kendaraan'),

                TextInput::make('nama_sopir')
                    ->label('Nama Sopir'),

                TextInput::make('total')
                    ->label('Total')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('bayar')
                    ->label('Bayar')
                    ->numeric()
                    ->reactive()
                    ->required()
                    ->afterStateUpdated(
                        fn($state, callable $get, callable $set) =>
                        $set('kembalian', $state - ($get('total') ?? 0))
                    ),

                TextInput::make('kembalian')
                    ->label('Kembalian')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('user_id')
                    ->default(fn() => auth()->id())
                    ->disabled()
                    ->dehydrated()
                    ->required(),
            ]);
    }
}
