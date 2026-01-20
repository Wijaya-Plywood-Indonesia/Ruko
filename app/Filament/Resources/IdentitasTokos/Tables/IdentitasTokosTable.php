<?php

namespace App\Filament\Resources\IdentitasTokos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IdentitasTokosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('kode_toko')
                    ->wrap()
                    ->placeholder('Belum Terdaftar')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_toko')
                    ->wrap()
                    ->placeholder('Belum Terdaftar')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pemilik')
                    ->wrap()
                    ->placeholder('Belum Terdaftar')
                    ->toggleable(),

                TextColumn::make('telepon')
                    ->wrap()
                    ->placeholder('Belum Terdaftar')
                    ->toggleable(),

                TextColumn::make('email')
                    ->wrap()
                    ->placeholder('Belum Terdaftar')
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'aktif',
                        'danger' => 'nonaktif',
                    ]),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
                SelectFilter::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                    ]),
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
