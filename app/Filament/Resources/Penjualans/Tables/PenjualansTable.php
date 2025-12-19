<?php

namespace App\Filament\Resources\Penjualans\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PenjualansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_nota')
                    ->searchable(),
                TextColumn::make('tanggal')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('nama_customer')
                    ->searchable(),
                TextColumn::make('metode_pembayaran'),
                TextColumn::make('bank')
                    ->searchable(),
                TextColumn::make('no_rekening')
                    ->searchable(),
                TextColumn::make('kendaraan')
                    ->searchable(),
                TextColumn::make('nama_sopir')
                    ->searchable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR', locale: 'id_ID')
                    ->sortable(),

                TextColumn::make('bayar')
                    ->label('Bayar')
                    ->money('IDR', locale: 'id_ID')
                    ->sortable(),

                TextColumn::make('kembalian')
                    ->label('Kembalian')
                    ->money('IDR', locale: 'id_ID')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('cetak')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->url(fn($record) => route('nota.cetak', $record))
                    ->openUrlInNewTab(),
                Action::make('suratJalan')
                    ->label('Cetak Surat Jalan')
                    ->icon('heroicon-o-truck')
                    ->url(fn($record) => route('surat-jalan.cetak', $record))
                    ->openUrlInNewTab(),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
