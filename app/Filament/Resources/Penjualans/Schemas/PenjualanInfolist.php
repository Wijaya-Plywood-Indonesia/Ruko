<?php

namespace App\Filament\Resources\Penjualans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class PenjualanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Nota')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('no_nota')
                            ->label('No Nota')
                            ->weight(FontWeight::Bold)
                            ->copyable(),

                        TextEntry::make('tanggal')
                            ->label('Tanggal')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('nama_customer')
                            ->label('Customer'),
                    ]),

                Section::make('Pembayaran')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('metode_pembayaran')
                            ->label('Metode')
                            ->badge()
                            ->color(fn($state) => $state === 'TUNAI' ? 'success' : 'warning'),

                        TextEntry::make('status_transaksi')
                            ->label('STATUS'),


                        TextEntry::make('bank')
                            ->visible(fn($record) => $record->metode_pembayaran === 'TRANSFER'),

                        TextEntry::make('no_rekening')
                            ->label('No Rekening')
                            ->visible(fn($record) => $record->metode_pembayaran === 'TRANSFER'),

                        TextEntry::make('total')
                            ->money('IDR', locale: 'id_ID')
                            ->weight(FontWeight::Bold),

                        TextEntry::make('bayar')
                            ->money('IDR', locale: 'id_ID'),

                        TextEntry::make('kembalian')
                            ->money('IDR', locale: 'id_ID')
                            ->color(fn($state) => $state < 0 ? 'danger' : 'success'),
                    ]),

                Section::make('Pengiriman')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('kendaraan')
                            ->label('Kendaraan'),

                        TextEntry::make('nama_sopir')
                            ->label('Nama Sopir'),

                        TextEntry::make('plat_kendaraan')
                            ->placeholder('Belum Input NoPol')
                            ->label('Nomor Polisi Kendaraan'),

                    ]),

                Section::make('Metadata')

                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Kasir'),

                        TextEntry::make('validator.name')
                            ->placeholder('Belum Divalidasi')
                            ->label('Validasi'),

                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diubah')
                            ->dateTime('d M Y H:i'),
                    ]),
            ]);
    }
}
