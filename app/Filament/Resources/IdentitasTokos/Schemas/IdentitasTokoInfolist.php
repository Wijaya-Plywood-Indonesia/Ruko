<?php

namespace App\Filament\Resources\IdentitasTokos\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IdentitasTokoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                Section::make('Informasi Toko')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('kode_toko')
                                    ->label('Kode Toko'),

                                TextEntry::make('nama_toko')
                                    ->label('Nama Toko'),

                                TextEntry::make('pemilik')
                                    ->label('Pemilik')
                                    ->placeholder('-'),

                                TextEntry::make('telepon')
                                    ->label('Telepon')
                                    ->placeholder('-'),

                                TextEntry::make('email')
                                    ->label('Email')
                                    ->placeholder('-'),

                                TextEntry::make('status')
                                    ->badge()
                                    ->label('Status')
                                    ->colors([
                                        'success' => 'aktif',
                                        'danger' => 'nonaktif',
                                    ]),
                            ]),

                        TextEntry::make('alamat')
                            ->label('Alamat')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
