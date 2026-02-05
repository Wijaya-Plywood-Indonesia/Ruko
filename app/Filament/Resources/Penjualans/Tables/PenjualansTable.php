<?php

namespace App\Filament\Resources\Penjualans\Tables;

use App\Filament\Resources\Penjualans\PenjualanResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PenjualansTable
{
    public static function configure(Table $table): Table
    {
        // dd(PenjualanResource::getUrl('preview'));
        // admin penjualans preview
        return $table
            ->columns([
                TextColumn::make('no_nota')
                    ->searchable(),

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
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tanggal')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('keterangan')
                    ->placeholder('kosong')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime()
                    ->sortable(),

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
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('validasi_transaksi')
                    ->label('Validasi Transaksi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')

                    // Muncul hanya jika BELUM divalidasi
                    ->visible(fn($record) => empty($record->validated_by))

                    // Pembuat transaksi TIDAK boleh validasi
                    ->disabled(
                        fn($record) =>
                        $record->user_id === filament()->auth()->id()
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
                        // dd([
                        //     'auth_default_id' => auth()->id(),
                        //     'auth_filament_id' => filament()->auth()->id(),
                        //     'record_user_id' => $record->user_id,
                        //     'record_user_id_type' => gettype($record->user_id),
                        //     'auth_default_type' => gettype(auth()->id()),
                        //     'auth_filament_type' => gettype(filament()->auth()->id()),
                        //     'strict_compare' => $record->user_id === filament()->auth()->id(),
                        //     'loose_compare' => $record->user_id == filament()->auth()->id(),
                        //     'full_record' => $record->toArray(),
                        // ]);
                        // HARD BACKEND PROTECTION
                        if ($record->user_id === filament()->auth()->id()) {
                            Notification::make()
                                ->title('Anda tidak boleh memvalidasi transaksi sendiri')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'validated_by' => filament()->auth()->id(),
                            'status_transaksi' => $data['status_transaksi'],
                        ]);

                        Notification::make()
                            ->title('Transaksi berhasil divalidasi')
                            ->success()
                            ->send();
                    }),

                Action::make('batal_validasi')
                    ->label('Batal Validasi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()

                    ->visible(
                        fn($record) =>
                        !empty($record->validated_by)
                        && $record->status_transaksi !== 'LUNAS'
                    )

                    ->action(function ($record) {
                        $record->update([
                            'validated_by' => null,
                            'status_transaksi' => 'BELUM DIBAYAR',
                        ]);

                        Notification::make()
                            ->title('Validasi transaksi berhasil dibatalkan')
                            ->danger()
                            ->send();
                    }),
                ViewAction::make(),
                //  EditAction::make(),
                Action::make('cetak')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->color('primary') // 🔵 Biru
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

                Action::make('cetakThermal')
                    ->label('Cetak Thermal')
                    ->icon('heroicon-o-printer')
                    ->color('success') // 🟢 Hijau
                    ->url(fn($record) => route('nota.cetakThermal', $record))
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

                Action::make('suratJalan')
                    ->label('Cetak Surat Jalan')
                    ->icon('heroicon-o-truck')
                    ->color('warning') // 🟠 Kuning / Orange
                    ->url(fn($record) => route('surat-jalan.penjualan.cetak', $record))
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
                            ->default(fn($record) => $record->keterangan)
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
