<?php

namespace App\Filament\Resources\Barangs\Schemas;

use Filament\Infolists\Components\IconEntry;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BarangInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Informasi Barang')
                    ->schema([
                        TextEntry::make('kode_barang')
                            ->label('Kode Barang')
                            ->copyable(),

                        TextEntry::make('barcode')
                            ->label('Barcode')
                            ->placeholder('-'),

                        TextEntry::make('nama_barang')
                            ->label('Nama Barang')
                            ->weight('bold'),

                        TextEntry::make('kategori.nama_kategori')
                            ->label('Kategori')
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('satuan.nama_satuan')
                            ->label('Satuan')
                            ->badge()
                            ->color('info'),
                    ])
                    ->columns(2),

                Section::make('Harga & Stok')
                    ->schema([
                        TextEntry::make('harga_beli')
                            ->label('HPP')
                            ->money('IDR'),

                        TextEntry::make('harga_jual')
                            ->label('Harga Jual')
                            ->money('IDR')
                            ->weight('bold'),

                        TextEntry::make('stok_minimum')
                            ->label('Stok Minimum')
                            ->suffix(fn($record) => ' ' . $record->satuan?->nama_satuan)
                            ->badge()
                            ->color(fn(int $state) => $state > 0 ? 'warning' : 'gray'),

                        IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ])
                    ->columns(2),

                Section::make('Informasi Sistem')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diubah')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
