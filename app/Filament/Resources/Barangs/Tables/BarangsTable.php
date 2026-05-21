<?php

namespace App\Filament\Resources\Barangs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BarangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('kode_barang')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('nama_barang')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('satuan.nama_satuan')
                    ->label('Satuan')
                    ->badge()
                    ->color('info'),

                TextColumn::make('harga_beli')
                    ->label('HPP')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('stok_minimum')
                    ->label('Stok Min')
                    ->badge()
                    ->color(fn(int $state) => $state > 0 ? 'warning' : 'gray'),

                // ── TAMPILAN AKUN PERSEDIAAN ────────────────────────────────
                TextColumn::make('subAnakAkun.kode_sub_anak_akun')
                    ->label('Akun Persediaan')
                    ->badge()
                    ->color('warning')
                    ->placeholder('Belum diset')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas('subAnakAkun', function (Builder $q) use ($search) {
                            $q->where('kode_sub_anak_akun', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('subAnakAkun.nama_sub_anak_akun')
                    ->label('Nama Akun Persediaan')
                    ->placeholder('—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas('subAnakAkun', function (Builder $q) use ($search) {
                            $q->where('nama_sub_anak_akun', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── TAMPILAN AKUN PENDAPATAN ────────────────────────────────
                TextColumn::make('akunPendapatan.kode_sub_anak_akun')
                    ->label('Akun Pendapatan')
                    ->badge()
                    ->color('success')
                    ->placeholder('Belum diset'),
                
                // ── TAMPILAN AKUN HPP ───────────────────────────────────────
                TextColumn::make('akunHpp.kode_sub_anak_akun')
                    ->label('Akun HPP')
                    ->badge()
                    ->color('danger')
                    ->placeholder('Belum diset'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                // nanti bisa tambah filter kategori / status
            ])
            ->recordActions([
                // ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nama_barang');
    }
}