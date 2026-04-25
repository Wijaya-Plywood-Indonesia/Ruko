<?php

namespace App\Filament\Resources\Presensis\RelationManagers;

use Carbon\CarbonPeriod;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DetailPresensiRelationManager extends RelationManager
{
    protected static string $relationship = 'detailPresensi';

    public function isReadOnly(): bool
    {
        return false;
    }

    // Time Options For Select Time Purpose
    public static function timeOptions(): array
    {
        return collect(CarbonPeriod::create('00:00', '1 hour', '23:00')->toArray())
            ->mapWithKeys(fn($time) => [
                $time->format('H:i') => $time->format('H.i'),
            ])
            ->toArray();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->options(self::timeOptions())
                    ->default('06:00')
                    ->required()
                    ->searchable()
                    // Menyimpan ke DB sebagai 'HH:MM:00'
                    ->dehydrateStateUsing(fn($state) => $state ? $state . ':00' : null)
                    // Menampilkan di form hanya 'HH:MM'
                    ->formatStateUsing(fn($state) => $state ? substr($state, 0, 5) : null),

                // --- JAM PULANG (Select dengan Options khusus) ---
                Select::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->options(self::timeOptions())
                    ->default('16:00')
                    ->required()
                    ->searchable()
                    ->dehydrateStateUsing(fn($state) => $state ? $state . ':00' : null)
                    ->formatStateUsing(fn($state) => $state ? substr($state, 0, 5) : null),

                // --- ID PEGAWAI (Relation: pegawai) ---
                Select::make('id_pegawai')
                    ->label('Pilih Pegawai')
                    ->relationship('pegawai', 'nama_lengkap', function (Builder $query) {
                        // Ambil ID Presensi (Parent) yang sedang aktif
                        $presensiId = $this->getOwnerRecord()->id;

                        // Filter: Hanya tampilkan pegawai yang BELUM ada di detail_presensi untuk presensi_id ini
                        return $query->whereDoesntHave('detailPresensi', function (Builder $q) use ($presensiId) {
                            $q->where('id_presensi', $presensiId);
                        });
                    })
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('ijin')
                    ->label('Ijin')
                    ->maxLength(255),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->maxLength(255),

                Textarea::make('hasil')
                    ->label('Hasil')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('detailPresensi')
            ->columns([
                TextColumn::make('pegawai.nama_lengkap')
                    ->label('Nama Pegawai')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->pegawai
                            ? "{$record->pegawai->nama_lengkap}"
                            : '-'
                    )
                    ->sortable()
                    ->searchable(),

                // Menampilkan jam saja (format 24 jam)
                TextColumn::make('jam_masuk')
                    ->label('Masuk')
                    ->dateTime('H:i'), // Gunakan 'H:i' untuk jam:menit

                // Menampilkan jam saja (format 24 jam)
                TextColumn::make('jam_pulang')
                    ->label('Pulang')
                    ->dateTime('H:i'), // Gunakan 'H:i' untuk jam:menit

                TextColumn::make('ijin')
                    ->label('Ijin')
                    ->default('-')
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->default('-')
                    ->color('primary')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('hasil')
                    ->label('Hasil')
                    ->default('-')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
