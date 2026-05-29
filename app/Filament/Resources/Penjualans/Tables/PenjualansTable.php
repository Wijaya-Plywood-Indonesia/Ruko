<?php

namespace App\Filament\Resources\Penjualans\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PenjualansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_nota')->searchable(),

                TextColumn::make('status_transaksi')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'LUNAS' => 'success',
                        'COD' => 'warning',
                        'PENDING' => 'gray',
                        'BELUM DIBAYAR' => 'danger',
                        'DIBATALKAN' => 'danger',
                        default => 'secondary',
                    })
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('validator.name')
                    ->label('Validator')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Belum Divalidasi') // Menampilkan teks jika belum ada yang validasi
                    ->badge() // Opsional: menjadikannya badge agar lebih menonjol
                    ->color(fn($state) => $state ? 'success' : 'gray') // Warna hijau jika ada validator, abu-abu jika belum
                    ->toggleable(),

                TextColumn::make('tanggal')->dateTime()->sortable(),

                TextColumn::make('keterangan')
                    ->placeholder('kosong')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('keterangan_pembayaran')
                    ->placeholder('kosong')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nama_customer')
                    ->searchable()
                    ->placeholder('Tidak Dicatat'),

                TextColumn::make('metode_pembayaran')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total')
                    ->label('Total Pembelian')
                    ->money('IDR', locale: 'id_ID')
                    ->sortable(),
            ])

            ->defaultSort('created_at', 'desc')

            ->recordActions([

                // ✅ VALIDASI TRANSAKSI
                Action::make('validasi_transaksi')
                    ->label('Validasi Transaksi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()

                    ->visible(
                        fn($record) => empty($record->validated_by)
                            && !in_array($record->status_transaksi, ['LUNAS', 'COD', 'DIBATALKAN'])
                    )

                    ->disabled(
                        fn($record) =>
                        $record->user_id === filament()->auth()->id()
                            && !filament()->auth()->user()->hasRole('super_admin')
                    )

                    ->modalHeading('Validasi Transaksi')
                    ->modalSubmitActionLabel('Simpan Validasi')

                    ->form([
                        TextInput::make('validator_name')
                            ->label('Validator')
                            ->default(fn() => filament()->auth()->user()->name)
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('status_transaksi')
                            ->label('Status Transaksi')
                            ->options([
                                'LUNAS' => 'LUNAS',
                                'COD' => 'COD',
                                'PENDING' => 'PENDING',
                                'DIBATALKAN' => 'DIBATALKAN',
                            ])
                            ->required(),
                    ])

                    ->action(function ($record, array $data) {

                        if (
                            $record->user_id === filament()->auth()->id()
                            && !filament()->auth()->user()->hasRole('super_admin')
                        ) {
                            Notification::make()
                                ->title('Tidak boleh validasi transaksi sendiri')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (!empty($record->validated_by)) {
                            Notification::make()
                                ->title('Transaksi sudah divalidasi')
                                ->warning()
                                ->send();
                            return;
                        }

                        $statusBaru  = $data['status_transaksi'];
                        $validatorId = filament()->auth()->id();

                        DB::transaction(function () use ($record, $statusBaru, $validatorId) {

                            if ($statusBaru === 'LUNAS') {
                                // Penyesuaian stok
                                app(StokPenyesuaianService::class)
                                    ->lunas($record->id);

                                // Buat jurnal pembantu otomatis
                                app(JurnalPenjualanTelurService::class)
                                    ->buatJurnalDariPenjualan($record, $validatorId);
                            }

                            $record->update([
                                'validated_by'     => $validatorId,
                                'status_transaksi' => $statusBaru,
                            ]);
                        });

                        Notification::make()
                            ->title('Transaksi berhasil divalidasi')
                            ->success()
                            ->send();
                    }),

                // ❌ BATAL VALIDASI
                Action::make('batal_validasi')
                    ->label('Batal Validasi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()

                    ->visible(
                        fn($record) =>
                        !empty($record->validated_by)
                            && filament()->auth()->user()->hasRole('super_admin')
                    )

                    ->action(function ($record) {

                        if (empty($record->validated_by)) {
                            Notification::make()
                                ->title('Transaksi belum divalidasi')
                                ->warning()
                                ->send();
                            return;
                        }

                        $userId = filament()->auth()->id();

                        DB::transaction(function () use ($record, $userId) {

                            if ($record->status_transaksi === 'LUNAS') {
                                // Balik stok
                                app(StokPenyesuaianService::class)
                                    ->batalLunas($record->id);

                                // Buat jurnal balik otomatis
                                app(JurnalBalikService::class)
                                    ->buatJurnalBalikDariNota($record->no_nota, $userId);
                            }

                            $record->update([
                                'validated_by'     => null,
                                'status_transaksi' => 'BELUM DIBAYAR',
                            ]);
                        });

                        Notification::make()
                            ->title('Validasi dibatalkan & jurnal balik telah dibuat')
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),

                // 🖨 CETAK
                Action::make('cetak')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->url(fn($record) => route('nota.cetak', $record))
                    ->openUrlInNewTab()
                    ->visible(
                        fn($record) =>
                        !empty($record->validated_by)
                            && !in_array($record->status_transaksi, [
                                'DIBATALKAN',
                                'BELUM DIBAYAR',
                                'PENDING',
                            ])
                    ),

                Action::make('edit_keterangan')
                    ->label('Edit Keterangan')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->modalHeading('Edit Keterangan')
                    ->modalSubmitActionLabel('Simpan')
                    ->form([
                        TextInput::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Masukkan keterangan...')
                            ->maxLength(255),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'keterangan' => $data['keterangan'],
                        ]);

                        Notification::make()
                            ->title('Keterangan berhasil diperbarui')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->visible(function ($record) {
                        $user = filament()->auth()->user();
                        // Super Admin selalu bisa lihat tombol
                        if ($user->hasRole('super_admin')) {
                            return true;
                        }
                        // Staff hanya bisa lihat jika belum divalidasi
                        return empty($record->validated_by);
                    })
                    ->requiresConfirmation()
                    ->action(function ($record, DeleteAction $action) {

                        // 1. Cek relasi yang ada di model Penjualan
                        $adaDetailBarang = $record->details()->exists();
                        $adaReturn       = $record->returns()->exists();

                        // 2. Logika validasi
                        if ($adaDetailBarang || $adaReturn) {
                            $alasan = $adaReturn
                                ? 'Sudah terdapat data retur yang terikat pada nota ini.'
                                : 'Masih terdapat rincian detail barang (item) dalam nota.';

                            Notification::make()
                                ->danger()
                                ->title('Data Gagal Dihapus!')
                                ->body("Nota {$record->no_nota} tidak dapat dihapus karena: {$alasan} Silakan hapus data relasi terlebih dahulu.")
                                ->persistent()
                                ->send();

                            // Menghentikan proses penghapusan
                            $action->halt();
                        }

                        // 3. Jika lolos pemeriksaan, hapus record
                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title('Berhasil Dihapus')
                            ->body("Data penjualan Nota {$record->no_nota} telah berhasil dihapus dari sistem.")
                            ->send();
                    }),
            ])
            ->headerActions([
                //
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
