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
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'AKTIF',
                        'danger' => 'NONAKTIF',
                    ])
                    ->label('Status'),
                TextColumn::make('nik')
                    ->label('NIK')
                    ->sortable()
                    ->searchable(),

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

                TextColumn::make('foto_ktp')
                    ->label('KTP')
                    ->formatStateUsing(fn($state) => $state ? 'Lihat KTP' : '-')
                    ->url(fn($record) => $record->foto_ktp ? asset('storage/' . $record->foto_ktp) : null)
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-eye')
                    ->color('primary')->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('foto_pegawai')
                    ->label('Foto Pegawai')
                    ->badge()
                    ->formatStateUsing(fn() => 'Lihat Foto')
                    ->url(fn($record) => asset('storage/' . $record->foto_pegawai))
                    ->openUrlInNewTab(),

                TextColumn::make('telepon')
                    ->label('Telepon')->toggleable(isToggledHiddenByDefault: true),



                TextColumn::make('tanggal_masuk')
                    ->label('TGL Masuk')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
