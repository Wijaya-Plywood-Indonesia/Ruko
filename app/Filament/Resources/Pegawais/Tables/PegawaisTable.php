<?php

namespace App\Filament\Resources\Pegawais\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PegawaisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_lengkap')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),


                TextColumn::make('nama_panggilan')
                    ->label('Panggilan')
                    ->searchable(),

                BadgeColumn::make('jenis_kelamin')
                    ->label('JK')
                    ->colors([
                        'primary' => 'L',
                        'pink' => 'P',
                    ])
                    ->formatStateUsing(
                        fn($state) =>
                        $state === 'L' ? 'Laki-laki' : 'Perempuan'
                    ),


                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'AKTIF',
                        'danger' => 'NONAKTIF',
                    ])
                    ->label('Status'),


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
                SelectFilter::make('status')
                    ->options([
                        'AKTIF' => 'Aktif',
                        'NONAKTIF' => 'Nonaktif',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
