<?php

namespace App\Filament\Resources\BarangMasuks\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BarangMasuksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')
                    ->label("Tanggal Barang Masuk")
                    ->date()
                    ->sortable(),
                TextColumn::make('penerima_barang')
                    ->label('Penerima barang')
                    ->searchable(),
                TextColumn::make('nomor_nota')
                    ->label("Nomor nota")
                    ->searchable(),
                TextColumn::make('created_by')
                    ->label('Dibuat Oleh')
                    ->searchable(),
                TextColumn::make('validated_by')
                    ->label('Divalidasi Oleh')
                    // Kondisi awal: Jika null, tampilkan 'Belum Validasi'
                    ->default('Belum Validasi')
                    ->badge()
                    // Memberikan warna merah jika belum validasi, hijau jika sudah
                    ->color(fn($state) => $state === 'Belum Validasi' ? 'danger' : 'success')
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
                Action::make('validate')
                    ->label('Validasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    // 1. Sembunyikan tombol jika data SUDAH divalidasi
                    ->hidden(fn($record) => $record->validated_by !== null)

                    // 2. Logika Hak Akses (Siapa yang bisa melihat tombol ini)
                    ->visible(function ($record) {
                        $user = Auth::user();

                        // JIKA SUPER ADMIN: Bisa memvalidasi siapapun (termasuk dirinya sendiri)
                        if ($user->hasRole('super_admin')) {
                            return true;
                        }

                        // JIKA USER BIASA: Tombol HANYA muncul jika dia BUKAN orang yang membuat record tersebut
                        // Ini memastikan harus ada 2 user berbeda (Pembuat & Validator)
                        return $record->created_by !== $user->name;
                    })

                    // 3. Eksekusi Validasi
                    ->action(function ($record) {
                        $record->update([
                            'validated_by' => 'Divalidasi oleh ' . Auth::user()->name,
                        ]);
                    })
                    ->successNotificationTitle('Data Berhasil Divalidasi'),
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
