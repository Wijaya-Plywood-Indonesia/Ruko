<?php

namespace App\Filament\Resources\RekeningPerusahaans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RekeningPerusahaansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pemilik_rekening')
                    ->label('Pemilik')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_bank')
                    ->label('Bank')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('no_rekening')
                    ->label('No Rekening')
                    ->searchable(),

                TextColumn::make('atas_nama')
                    ->label('Atas Nama')
                    ->searchable(),

                // ── Kolom akun jurnal ─────────────────────────────────────────
                TextColumn::make('subAnakAkun.kode_sub_anak_akun')
                    ->label('Kode Akun')
                    ->badge()
                    ->color('warning')
                    ->placeholder('Belum diset')
                    ->searchable(),

                TextColumn::make('subAnakAkun.nama_sub_anak_akun')
                    ->label('Nama Akun Jurnal')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([])
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