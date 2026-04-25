<?php

namespace App\Filament\Resources\ProduksiPakans\Tables;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProduksiPakansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_produksi')
                    ->label('Tanggal')
                    ->formatStateUsing(function ($state) {
                        if (!$state)
                            return '-';

                        return Carbon::parse($state)
                            ->locale('id')
                            ->translatedFormat('l , d F Y');
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('komposisi.barang.nama_barang')
                    ->label('Resep / Produk')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->searchable(),

                TextColumn::make('created_by')
                    ->label('Dibuat Oleh')
                    ->formatStateUsing(fn($record) => "{$record->created_by} (" . $record->created_at->format('d/m/Y H:i') . ")")
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('validated_by')
                    ->label('Divalidasi Oleh')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state || $state === 'Belum Validasi') {
                            return 'Belum Validasi';
                        }
                        // Menggunakan updated_at sebagai asumsi waktu validasi
                        return "{$state} (" . $record->updated_at->format('d/m/Y H:i') . ")";
                    })
                    // Kondisi awal: Jika null, tampilkan 'Belum Validasi'
                    ->default('Belum Validasi')
                    ->badge()
                    // Memberikan warna merah jika belum validasi, hijau jika sudah
                    ->color(fn($state) => $state === 'Belum Validasi' ? 'danger' : 'success')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([

                Action::make('updateKeterangan')
                    ->visible(fn($record) => Auth::user()->hasRole('super_admin') || $record->validated_by === null)
                    // Logika Label Dinamis
                    ->label(fn($record) => $record->keterangan ? 'Perbarui Keterangan' : 'Tambah Keterangan')
                    ->icon('heroicon-m-chat-bubble-bottom-center-text')
                    ->color(fn($record) => $record->keterangan ? 'info' : 'gray')

                    // Membuat Popup (Modal)
                    ->modalHeading('Keterangan Produksi')
                    ->modalSubmitActionLabel('Simpan')
                    ->form([
                        Textarea::make('keterangan')
                            ->label('Isi Keterangan')
                            ->placeholder('Tulis keterangan di sini...')
                            ->rows(5)
                            ->required(),
                    ])
                    // Mengisi form dengan data lama saat popup terbuka
                    ->fillForm(fn($record): array => [
                        'keterangan' => $record->keterangan,
                    ])
                    // Logika Simpan
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'keterangan' => $data['keterangan'],
                        ]);

                        Notification::make()
                            ->title('Keterangan berhasil disimpan')
                            ->success()
                            ->send();
                    }),
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
                            'validated_by' => 'Divalidasi oleh ' . Auth::user()->name . ' pada ' . now()->translatedFormat('d M Y, H:i'),
                        ]);
                    })
                    ->successNotificationTitle('Data Berhasil Divalidasi'),
                ViewAction::make()->visible(fn($record) => Auth::user()->hasRole('super_admin') || $record->validated_by === null),
                EditAction::make()->visible(fn($record) => Auth::user()->hasRole('super_admin') || $record->validated_by === null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn($record) => Auth::user()->hasRole('super_admin') || $record->validated_by === null),
                ]),
            ]);
    }
}
