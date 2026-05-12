<?php

namespace App\Filament\Resources\AkunGroups\RelationManagers;

use App\Models\SubAnakAkun;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkAction as ActionsBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class SubAnakAkunsRelationManager extends RelationManager
{
    public function isReadOnly(): bool
    {
        return false;
    }

    protected static string $relationship = 'subAnakAkuns';

    protected static ?string $title = 'Daftar Sub Akun (Neraca)';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->isLeaf();
    }

    public function table(Table $table): Table
    {
        return $table
        ->selectable()
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
    ActionsBulkAction::make('detachSelected')
        ->label('Lepas Semua Dipilih')
        ->icon('heroicon-o-link-slash')
        ->requiresConfirmation()
        ->modalHeading('Lepas Sub Akun Terpilih')
        ->modalDescription('Apakah Anda yakin ingin melepas semua sub akun yang dipilih?')
        ->modalSubmitActionLabel('Ya, Lepas')
        ->deselectRecordsAfterCompletion()
        ->action(function (Collection $records) {

            $ids = $records->pluck('id');

            $this->ownerRecord
                ->subAnakAkuns()
                ->detach($ids);

        }),
]);
    }
}