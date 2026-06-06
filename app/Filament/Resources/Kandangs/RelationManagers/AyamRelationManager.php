<?php

namespace App\Filament\Resources\Kandangs\RelationManagers;

use App\Filament\Resources\Kandangs\KandangResource;
use App\Models\Kandang;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AyamRelationManager extends RelationManager
{
    protected static string $relationship = 'ayam';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('nama_batch')
                    ->label('Nama Batch'),

                DatePicker::make('tanggal_masuk')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->maxDate(now())
                    ->default(now())
                    ->live()
                    ->closeOnDateSelection()
                    ->suffixIcon('heroicon-o-calendar')
                    ->suffixIconColor('primary')
                    ->required(),

                TextInput::make('jumlah_awal')
                    ->label('Jumlah Ayam')
                    ->required()
                    ->numeric(),

                TextInput::make('usia_minggu')
                    ->label('Usia Ayam')
                    ->numeric()
                    ->default(1)
                    ->suffix('minggu')
                    ->required()
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($component, $record) {
                        if ($record) {
                            $component->state(round($record->usia / 7, 2));
                        }
                    }),

                Textarea::make('keterangan')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_batch')
            ->columns([
                TextColumn::make('nama_batch')
                    ->label('Nama Batch')
                    ->searchable(),

                TextColumn::make('tanggal_masuk')
                    ->label('Tanggal Masuk')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('jumlah_awal')
                    ->label('Jumlah Ayam')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('usia')
                    ->label('Usia Masuk')
                    ->state(fn($record) => round($record->usia / 7, 2))
                    ->suffix(' minggu')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('umur_format')
                    ->label('Usia Ayam')
                    ->state(fn($record) => $record->umur_format)
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tambahkan filter di sini jika diperlukan nanti
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['usia'] = isset($data['usia_minggu'])
                            ? (int) round((float) $data['usia_minggu'] * 7)
                            : 7;
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Ubah Data Ayam')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['usia'] = isset($data['usia_minggu'])
                            ? (int) round((float) $data['usia_minggu'] * 7)
                            : 7;
                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
