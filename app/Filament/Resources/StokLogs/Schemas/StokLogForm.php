<?php

namespace App\Filament\Resources\StokLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StokLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                /* =========================
                 |  RELASI UTAMA
                 ========================= */

                Select::make('barang_id')
                    ->label('Barang')
                    ->relationship('barang', 'nama') // sesuaikan field
                    ->searchable()
                    ->required(),

                Select::make('toko_id')
                    ->label('Toko')
                    ->relationship('toko', 'nama_toko') // sesuaikan field
                    ->searchable()
                    ->required(),

                /* =========================
                 |  TIPE & JUMLAH
                 ========================= */

                Select::make('tipe')
                    ->label('Tipe Transaksi')
                    ->options([
                        'pembelian' => 'Pembelian',
                        'penjualan' => 'Penjualan',
                        'mutasi_masuk' => 'Mutasi Masuk',
                        'mutasi_keluar' => 'Mutasi Keluar',
                        'adjustment' => 'Adjustment',
                        'retur' => 'Retur',
                    ])
                    ->required(),

                TextInput::make('qty')
                    ->label('Qty')
                    ->numeric()
                    ->minValue(1)
                    ->required(),

                /* =========================
                 |  STOK (READONLY)
                 ========================= */

                TextInput::make('stok_sebelum')
                    ->label('Stok Sebelum')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(), // tetap tersimpan

                TextInput::make('stok_sesudah')
                    ->label('Stok Sesudah')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),

                /* =========================
                 |  REFERENSI
                 ========================= */

                Select::make('referensi_type')
                    ->label('Referensi')
                    ->options([
                        'surat_jalan' => 'Surat Jalan',
                        // 'penjualan' => 'Penjualan',
                        // 'pembelian' => 'Pembelian',
                    ])
                    ->nullable(),

                TextInput::make('referensi_id')
                    ->label('ID Referensi')
                    ->numeric()
                    ->nullable(),
            ]);
    }
}
