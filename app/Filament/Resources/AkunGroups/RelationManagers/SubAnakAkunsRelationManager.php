<?php

namespace App\Filament\Resources\AkunGroups\RelationManagers;

use App\Models\SubAnakAkun;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SubAnakAkunsRelationManager extends RelationManager
{
    public function isReadOnly(): bool
    {
        return false;
    }

    protected static string $relationship = 'subAnakAkuns';

    protected static ?string $title = 'Daftar Sub Akun (Neraca)';

    /*
    |--------------------------------------------------------------------------
    | Leaf Only — hanya tampil di group yang tidak punya children
    |--------------------------------------------------------------------------
    */

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->isLeaf();
    }

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_sub_anak_akun')
            ->columns([
                TextColumn::make('kode_sub_anak_akun')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('nama_sub_anak_akun')
                    ->label('Nama Sub Akun')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('anakAkun.nama_anak_akun')
                    ->label('Anak Akun')
                    ->sortable(),

                TextColumn::make('saldo_normal')
                    ->label('Saldo Normal')
                    ->badge()
                    ->color(fn($state) => strtolower($state ?? '') === 'kredit' ? 'danger' : 'success')
                    ->formatStateUsing(fn($state) => ucfirst(strtolower($state ?? '-'))),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Daftarkan Sub Akun')
                    ->preloadRecordSelect()
                    ->multiple()
                    ->recordTitle(
                        fn(SubAnakAkun $record) =>
                        "{$record->kode_sub_anak_akun} — {$record->nama_sub_anak_akun}"
                    )
                    ->recordSelectSearchColumns([
                        'kode_sub_anak_akun',
                        'nama_sub_anak_akun',
                    ])
                    ->recordSelectOptionsQuery(
                        // Hanya tampilkan sub akun yang belum terdaftar di group manapun
                        fn($query) => $query
                            ->where('status', 'aktif')
                            ->whereDoesntHave('akunGroups')
                            ->orderBy('kode_sub_anak_akun')
                    ),
            ])
            ->actions([
                DetachAction::make()
                    ->label('Lepas'),
            ])
            ->bulkActions([
                DetachBulkAction::make()
                    ->label('Lepas Semua Dipilih'),
            ]);
    }
}