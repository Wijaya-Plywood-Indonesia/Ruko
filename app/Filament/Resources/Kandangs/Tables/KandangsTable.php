<?php

namespace App\Filament\Resources\Kandangs\Tables;

use App\Models\Kandang;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KandangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_kandang')
                    ->searchable(),
                IconColumn::make('is_aktif')
                    ->boolean(),
                // Virtual column — nilai dihitung dari Model, bukan dari DB langsung
                TextColumn::make('populasi_saat_ini')
                    ->label('Banyak Ayam Saat Ini')
                    ->state(fn(Kandang $record) => $record->populasiEfektif())
                    ->suffix(' ekor')
                    ->sortable(false),

                // Virtual column — hitung jumlah batch di kandang ini
                TextColumn::make('jumlah_batch')
                    ->label('Batch Aktif')
                    ->state(fn(Kandang $record) => $record->ayams()->count())
                    ->suffix(' batch')
                    ->sortable(false),

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
