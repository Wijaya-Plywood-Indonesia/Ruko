<?php

namespace App\Filament\Resources\StokLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StokLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /* =========================
                 |  RELASI
                 ========================= */

                TextColumn::make('barang.nama_barang')
                    ->label('Barang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('toko.nama_toko')
                    ->label('Toko')
                    ->searchable()
                    ->sortable(),

                /* =========================
                 |  TRANSAKSI
                 ========================= */

                TextColumn::make('tipe')
                    ->badge()
                    ->label('Tipe')
                    ->colors([
                        'success' => ['pembelian', 'mutasi_masuk', 'retur'],
                        'danger' => ['penjualan', 'mutasi_keluar'],
                        'warning' => ['adjustment'],
                    ])
                    ->formatStateUsing(fn($state) => str_replace('_', ' ', ucfirst($state)))
                    ->sortable(),

                TextColumn::make('qty')
                    ->label('Qty')
                    ->alignCenter()
                    ->numeric(decimalPlaces: 2)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stok_sebelum')
                    ->label('Stok Sebelum')
                    ->searchable()
                    ->alignCenter()
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('stok_sesudah')
                    ->label('Stok Sesudah')
                    ->alignCenter()
                    ->searchable()
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                /* =========================
                 |  REFERENSI
                 ========================= */

                TextColumn::make('referensi_type')
                    ->label('Referensi')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state)))
                    ->toggleable(),

                TextColumn::make('referensi_id')
                    ->label('ID Ref')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                /* =========================
                 |  META
                 ========================= */

                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // nanti bisa tambahin filter Masuk / Keluar
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(false), // audit log biasanya tidak diedit
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}