<?php

namespace App\Filament\Resources\SuratJalans\Tables;

use App\Models\SuratJalan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SuratJalansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_surat_jalan')
                    ->searchable(),
                TextColumn::make('tanggal_kirim')
                    ->date()
                    ->sortable(),

                TextColumn::make('tokoAsal.nama_toko')
                    ->label('Dari'),
                TextColumn::make('tokoTujuan.nama_toko')
                    ->label('Ke'),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'dikirim',
                        'success' => 'diterima',
                        'danger' => 'ditolak',
                    ]),
                TextColumn::make('created_by')
                    ->label("Pembuat")
                    ->numeric()
                    ->sortable(),
                TextColumn::make('Validated_by')
                    ->label("Validator")
                    ->numeric()
                    ->sortable(),

                TextColumn::make('nama_supir')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('jeniskendaraan')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('plat')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('validated_by')
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
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn(SuratJalan $record) => $record->status === 'draft'),
                DeleteAction::make()
                    ->visible(fn(SuratJalan $record) => $record->status === 'draft'),

            ])
            ->toolbarActions([
            ]);
    }
}
