<?php

namespace App\Filament\Resources\StokBarangTokos\Tables;

use App\Models\Barang;
use App\Models\IdentitasToko;
use App\Models\StokBarangToko;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class StokBarangTokosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('barang.nama_barang')
                    ->label('Barang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('toko.nama_toko')
                    ->label('Toko')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stok')
                    ->label('Stok')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Update')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('toko_id')
                    ->label('Filter Toko')
                    ->relationship('toko', 'nama_toko')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),

            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                Action::make('sinkronStokSemuaToko')
                    ->label('Sinkronkan Stok Semua Toko')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function () {

                        $barangIds = Barang::pluck('id');
                        $tokoIds = IdentitasToko::pluck('id');

                        if ($barangIds->isEmpty() || $tokoIds->isEmpty()) {
                            Notification::make()
                                ->title('Barang atau Toko masih kosong')
                                ->warning()
                                ->send();
                            return;
                        }

                        DB::transaction(function () use ($barangIds, $tokoIds) {

                            $data = [];

                            foreach ($tokoIds as $tokoId) {
                                foreach ($barangIds as $barangId) {
                                    $data[] = [
                                        'barang_id' => $barangId,
                                        'toko_id' => $tokoId,
                                        'stok' => 0,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                }
                            }

                            DB::table('stok_barang_toko')->insertOrIgnore($data);
                        });

                        Notification::make()
                            ->title('Sinkronisasi stok semua toko berhasil')
                            ->success()
                            ->send();
                    }),

                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}