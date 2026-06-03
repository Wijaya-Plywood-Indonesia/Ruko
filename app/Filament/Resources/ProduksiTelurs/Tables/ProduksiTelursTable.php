<?php

namespace App\Filament\Resources\ProduksiTelurs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProduksiTelursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id_kandang')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('id_ayam')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('jumlah_telur_butir')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('jumlah_telur_retak')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('jumlah_telur_pecah')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('jumlah_ayam_mati')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('hen_day_production')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->searchable(),
                TextColumn::make('validated_by')
                    ->searchable(),
                TextColumn::make('validated_at')
                    ->dateTime()
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
