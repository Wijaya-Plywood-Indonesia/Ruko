<?php

namespace App\Filament\Resources\Pembelians\Tables;

use App\Models\Pembelian;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class PembeliansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_nota')
                    ->label('Nomor Nota')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('supplier_name')
                    ->label('Supplier')
                    ->searchable(),

                TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->money('IDR', locale: 'id_ID')
                    ->sortable(),

                // Status menggunakan logic badge seperti POS
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function ($record, $state) {
                        // Jika belum divalidasi, tampilkan 'Belum Diproses' (Draft)
                        if (empty($record->validated_by)) {
                            return Pembelian::labelStatus()[Pembelian::STATUS_DRAFT] ?? 'Belum Diproses';
                        }

                        // Jika sudah divalidasi, ambil label sesuai state dari model
                        return Pembelian::labelStatus()[$state] ?? $state;
                    })
                    ->color(function ($record, $state) {
                        // Jika belum divalidasi, beri warna abu-abu
                        if (empty($record->validated_by)) {
                            return 'gray';
                        }

                        // Mapping warna berdasarkan konstanta model
                        return match ($state) {
                            Pembelian::STATUS_LUNAS => 'success',
                            Pembelian::STATUS_CICILAN => 'warning',
                            Pembelian::STATUS_HUTANG => 'danger',
                            Pembelian::STATUS_BATAL => 'danger',
                            Pembelian::STATUS_DRAFT => 'gray',
                            default => 'secondary',
                        };
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('createdBy.name')
                    ->label('Admin/Purchasing')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('validatedBy.name')
                    ->label('Validator')
                    ->placeholder('Belum Validasi')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                // ✅ ACTION: VALIDASI PEMBELIAN
                Action::make('validasi_pembelian')
                    ->label('Validasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    // Sembunyikan jika sudah divalidasi atau dibatalkan
                    ->visible(fn($record) => empty($record->validated_by) && $record->status_transaksi !== Pembelian::STATUS_BATAL)
                    // Cegah validasi diri sendiri (kecuali super_admin)
                    ->disabled(fn($record) => $record->created_by === filament()->auth()->id() && !filament()->auth()->user()->hasRole('super_admin'))

                    ->form([
                        TextInput::make('validator_name')
                            ->label('Petugas Validasi')
                            ->default(fn() => filament()->auth()->user()->name)
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('status_transaksi')
                            ->label('Update Status Pembelian')
                            ->options(Pembelian::labelStatus())
                            ->required()
                            ->disableOptionWhen(fn(string $value): bool => $value === Pembelian::STATUS_DRAFT),
                    ])
                    ->action(function ($record, array $data) {
                        $validatorId = filament()->auth()->id();

                        DB::transaction(function () use ($record, $data, $validatorId) {
                            $record->update([
                                'validated_by'     => $validatorId,
                                'status_transaksi' => $data['status_transaksi'],
                                'tanggal_validasi' => now(),
                            ]);
                        });

                        Notification::make()
                            ->title('Pembelian Berhasil Divalidasi')
                            ->success()
                            ->send();
                    }),

                // ❌ ACTION: BATAL VALIDASI
                Action::make('batal_validasi')
                    ->label('Batal Validasi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    // Hanya muncul jika sudah divalidasi & user adalah super_admin
                    ->visible(fn($record) => !empty($record->validated_by) && filament()->auth()->user()->hasRole('super_admin'))
                    ->action(function ($record) {
                        DB::transaction(function () use ($record) {
                            // Logika jurnal balik atau kurangi stok bisa ditaruh di sini

                            $record->update([
                                'validated_by'     => null,
                                'status_transaksi' => 'BELUM DIBAYAR',
                            ]);
                        });

                        Notification::make()
                            ->title('Validasi telah dibatalkan')
                            ->warning()
                            ->send();
                    }),

                ViewAction::make(),
                EditAction::make()
                    ->visible(fn($record) => empty($record->validated_by)), // Edit hanya boleh sebelum validasi

                DeleteAction::make()
                    ->visible(fn() => filament()->auth()->user()->hasRole("super_admin"))
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
