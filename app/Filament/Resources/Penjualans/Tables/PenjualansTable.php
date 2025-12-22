<?php

namespace App\Filament\Resources\Penjualans\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PenjualansTable
{
    public static function configure(Table $table): Table
    {
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

                TextColumn::make('nama_customer')
                    ->searchable()
                    ->placeholder('Tidak Dicatat'),

                TextColumn::make('metode_pembayaran')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bank')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('no_rekening')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('kendaraan')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nama_sopir')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->label('Total Pembelian')
                    ->money('IDR', locale: 'id_ID')
                    ->sortable(),

                TextColumn::make('bayar')
                    ->label('Bayar')
                    ->money('IDR', locale: 'id_ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('kembalian')
                    ->label('Kembalian')
                    ->money('IDR', locale: 'id_ID')
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
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('validasi_transaksi')
                    ->label('Validasi Transaksi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')

                    // Hanya tampil jika BELUM divalidasi
                    ->visible(fn($record) => empty($record->validated_by))

                    // Kasir TIDAK boleh validasi
                    ->disabled(fn($record) => Auth::id() === $record->user_id)

                    ->modalHeading('Validasi Transaksi')
                    ->modalSubmitActionLabel('Simpan Validasi')

                    ->form([
                        TextInput::make('validator_name')
                            ->label('Validator')
                            ->default(Auth::user()->name)
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
                        // Safety check backend
                        if (Auth::id() === $record->user_id) {
                            throw new \Exception('Kasir tidak boleh memvalidasi transaksinya sendiri.');
                        }

                        $record->update([
                            'validated_by' => Auth::id(),
                            'status_transaksi' => $data['status_transaksi'],
                        ]);
                    }),
                ViewAction::make(),
                //  EditAction::make(),
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
                Action::make('suratJalan')
                    ->label('Cetak Surat Jalan')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->url(fn($record) => route('surat-jalan.cetak', $record))
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

            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
