<?php

namespace App\Filament\Resources\ReturnPenjualans\Tables;

use App\Services\StokPenyesuaianService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReturnPenjualansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_nota')
                    ->searchable(),

                TextColumn::make('status_return')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        // ['DIPROSES', 'DITOLAK', 'DITERIMA', 'SELESAI' ]
                        'SELESAI' => 'success',
                        'DIPROSES' => 'warning',
                        'DITERIMA' => 'success',
                        'PENDING' => 'warning',
                        'DITOLAK' => 'danger',
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
                    ->sortable()
                ,

                TextColumn::make('keterangan')
                    ->placeholder('kosong')
                    ->toggleable(isToggledHiddenByDefault: true)
                    // ->dateTime()
                    ->sortable(),

                TextColumn::make('nama_customer')
                    ->searchable()
                    ->placeholder('Tidak Dicatat'),

                TextColumn::make('metode_pembayaran')
                    ->toggleable(isToggledHiddenByDefault: true),


                TextColumn::make('bayar')
                    ->label('Total Bayar')
                    ->money('IDR', locale: 'id_ID')
                    ->sortable(),

                TextColumn::make('details_return_sum_qty') // Harus format: {relasi}_sum_{kolom}
                    ->label('Total Qty')
                    ->sum('details_return', 'qty')
                    ->default(0)
                    ->alignCenter()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('validasi_static')
                    ->label('Validasi Transaksi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')

                    // Muncul hanya jika BELUM divalidasi
                    ->visible(fn($record) => empty($record->validate_by))

                    // Pembuat transaksi TIDAK boleh validasi
                    ->disabled(
                        fn($record) =>
                        $record->user_id === filament()->auth()->id() && !filament()->auth()->user()->hasRole("super_admin")
                    )

                    ->modalHeading('Validasi Transaksi')
                    ->modalSubmitActionLabel('Simpan Validasi')

                    ->form([
                        TextInput::make('validator_name')
                            ->label('Validator')
                            ->default(fn() => filament()->auth()->user()->name)
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('status_return')
                            ->label('Status Retur')
                            ->options([
                                'DITOLAK' => 'DITOLAK',
                                'DITERIMA' => 'DITERIMA',
                                'PENDING' => 'PENDING',
                                'DIPROSES' => 'DIPROSES',
                                'SELESAI' => 'SELESAI',
                            ])
                            ->required(),
                    ])

                    ->action(function ($record, array $data) {
                        // HARD BACKEND PROTECTION
                        if ($record->user_id === filament()->auth()->id() && !filament()->auth()->user()->hasRole("super_admin")) {
                            Notification::make()
                                ->title('Anda tidak boleh memvalidasi retur sendiri')
                                ->danger()
                                ->send();

                            return;
                        }

                        // ! CALL SERVICE
                        $status = $data['status_return'];
                        if (in_array($status, ['DITERIMA', 'SELESAI'])) {
                            app(StokPenyesuaianService::class)
                                ->selesai($record->id);
                        }

                        $record->update([
                            'validate_by' => filament()->auth()->id(),
                            'status_return' => $status,
                        ]);

                        Notification::make()
                            ->title('Status Retur berhasil divalidasi')
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
                        !empty($record->validate_by)
                        &&
                        (
                            $record->status_return !== 'SELESAI' || filament()->auth()->user()->hasRole("super_admin")

                        )

                    )

                    ->action(function ($record) {
                        // ! CALL SERVICE
                        $status = $record->status_return;
                        if (in_array($status, ['DITERIMA', 'SELESAI'])) {
                            app(StokPenyesuaianService::class)
                                ->validasi_batal_dari_selesai($record->id);
                        }

                        $record->update([
                            'validate_by' => null,
                            'status_return' => 'DITOLAK',
                        ]);

                        Notification::make()
                            ->title('Validasi return berhasil dibatalkan')
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
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

                DeleteAction::make()
                    ->visible(fn($record) => filament()->auth()->user()->hasRole("super_admin"))

            ]);
    }
}
