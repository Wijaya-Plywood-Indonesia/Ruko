<?php

namespace App\Filament\Resources\DetailSuratJalans\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
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
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('qty_diterima')
                    ->numeric(decimalPlaces: 2)
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
                CreateAction::make()
                    ->label('Tambah Barang')
                    ->disabled(function (RelationManager $livewire) {
                        $nota = $livewire->getOwnerRecord();

                        // Disable jika SUDAH divalidasi
                        return $nota?->validated_by !== null;
                    })
                    ->tooltip('Nota sudah divalidasi, tidak bisa menambah barang'),
                Action::make('validasi_nota')
                    ->label('Validasi Nota')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (RelationManager $livewire) {
                        // Tombol hanya muncul jika BELUM divalidasi
                        return empty($livewire->ownerRecord->validated_by);
                    })
                    ->disabled(function (RelationManager $livewire) {
                        // Pembuat TIDAK boleh validasi
                        return $livewire->ownerRecord->created_by == auth()->id();
                    })
                    ->action(function (RelationManager $livewire) {

                        $nota = $livewire->ownerRecord;

                        $nota->update([
                            'validated_by' => auth()->id(),
                            'status' => 'dikirim',
                        ]);

                        Notification::make()
                            ->title('Nota berhasil divalidasi!')
                            ->success()
                            ->send();
                    })
                    ->after(function ($livewire) {
                        // Refresh komponen supaya status berubah
                        $livewire->dispatch('$refresh');
                    }),
                Action::make('batalkan_validasi')
                    ->label('Batalkan Validasi')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()

                    ->visible(function (RelationManager $livewire) {

                        $nota = $livewire->ownerRecord;

                        return auth()->user()->hasRole('super_admin')
                            && !empty($nota->validated_by);
                    })

                    ->action(function (RelationManager $livewire) {

                        $nota = $livewire->ownerRecord;

                        $nota->update([
                            'validated_by' => null,
                            'status' => 'draft',
                        ]);

                        Notification::make()
                            ->title('Validasi berhasil dibatalkan')
                            ->success()
                            ->send();
                    })

                    ->after(fn($livewire) => $livewire->dispatch('$refresh')),
            ])
            ->filters([
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
