<?php

namespace App\Filament\Resources\Ayams\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AyamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kandang.nama_kandang')
                    ->label("Kandang")
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nama_batch')
                    ->label('Nama Batch')
                    ->searchable(),

                TextColumn::make('tanggal_masuk')
                    ->date()
                    ->sortable(),

                TextColumn::make('jumlah_awal')
                    ->label('Jumlah Ayam Masuk')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('usia')
                    ->label('Usia Masuk')
                    ->numeric()
                    ->sortable()
                    ->suffix(' hari')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('umur_format')
                    ->label('Usia Ayam')
                    ->state(fn($record) => $record->umur_format)
                    ->badge()
                    ->color(fn($record): string => $record->umur_badge_color)
                    ->tooltip(function ($record): string {
                        // Populasi adalah milik kandang, bukan batch
                        // Gunakan method dari Model Kandang
                        $populasi  = $record->kandang->populasiEfektif();
                        $totalAwal = $record->kandang->totalAwal();
                        return "Populasi kandang: {$populasi} ekor dari {$totalAwal} ekor awal";
                    }),


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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
