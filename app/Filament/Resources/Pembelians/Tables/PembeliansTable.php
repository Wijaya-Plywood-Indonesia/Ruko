<?php

namespace App\Filament\Resources\Pembelians\Tables;

use App\Models\Pembelian;
use Filament\Actions\Action; // Boleh dihapus jika sudah tidak ada aksi custom lain
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

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
                    ->formatStateUsing(function ($state) {
                        return 'Rp ' . number_format($state, 0, ',', '.');
                    })
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                // Status menggunakan logic badge seperti POS
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function ($record, $state) {
                        if (empty($record->validated_by)) {
                            return Pembelian::labelStatus()[Pembelian::STATUS_DRAFT] ?? 'Belum Diproses';
                        }
                        return Pembelian::labelStatus()[$state] ?? $state;
                    })
                    ->color(function ($record, $state) {
                        if (empty($record->validated_by)) {
                            return 'gray';
                        }

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
                // Tombol Validasi dan Batal Validasi SUDAH DIHAPUS DARI SINI
                
                ViewAction::make(),
                EditAction::make()
                    ->visible(function ($record) {
                        $user = filament()->auth()->user();
                        if ($user->hasRole('super_admin')) {
                            return true;
                        }
                        return empty($record->validated_by);
                    }),

                DeleteAction::make()
                    ->visible(function ($record) {
                        $user = filament()->auth()->user();
                        if ($user->hasRole('super_admin')) {
                            return true;
                        }
                        return empty($record->validated_by);
                    })
                    ->requiresConfirmation()
                    ->action(function ($record, DeleteAction $action) {

                        $adaDetailBarang = $record->detailPembelians()->exists();
                        $adaRiwayatBayar = $record->metodePembayarans()->exists();

                        if ($adaDetailBarang || $adaRiwayatBayar) {
                            $alasan = $adaRiwayatBayar
                                ? 'Sudah terdapat data riwayat pembayaran/DP yang terikat.'
                                : 'Masih terdapat rincian detail barang di dalam keranjang nota.';

                            Notification::make()
                                ->danger()
                                ->title('Data Gagal Dihapus!')
                                ->body("Nota {$record->nomor_nota} tidak dapat dihapus karena: {$alasan} Silakan hapus data relasi terlebih dahulu.")
                                ->persistent()
                                ->duration(3000)
                                ->send();

                            $action->halt();
                        }

                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title('Berhasil Dihapus')
                            ->body("Data pembelian Nota {$record->nomor_nota} telah bersih dihapus dari sistem.")
                            ->duration(3000)
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => filament()->auth()->user()->hasRole('super_admin'))
                        ->successNotification(fn() => null)
                        ->successNotificationTitle(fn() => null)
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $gagalDihapus = [];
                            $berhasilDihapusCount = 0;

                            foreach ($records as $record) {
                                $adaDetailBarang = $record->detailPembelians()->exists();
                                $adaRiwayatBayar = $record->metodePembayarans()->exists();

                                if ($adaDetailBarang || $adaRiwayatBayar) {
                                    $gagalDihapus[] = $record->nomor_nota;
                                } else {
                                    $record->delete();
                                    $berhasilDihapusCount++;
                                }
                            }

                            if (count($gagalDihapus) > 0) {
                                $daftarNota = implode(', ', $gagalDihapus);

                                Notification::make()
                                    ->danger()
                                    ->title('Beberapa Data Gagal Dihapus!')
                                    ->body("Gagal menghapus nota: **{$daftarNota}** karena masih memiliki detail barang atau riwayat pembayaran yang terikat.")
                                    ->duration(3000)
                                    ->send();
                            }

                            if ($berhasilDihapusCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Hapus Massal Berhasil')
                                    ->body("Sebanyak {$berhasilDihapusCount} data pembelian yang aman telah berhasil dihapus dari sistem.")
                                    ->duration(3000)
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }
}