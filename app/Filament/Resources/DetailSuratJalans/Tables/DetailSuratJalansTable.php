<?php

namespace App\Filament\Resources\DetailSuratJalans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DetailSuratJalansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('barang.nama_barang')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('qty_kirim')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('qty_diterima')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()->label("Tambah Barang !"),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
