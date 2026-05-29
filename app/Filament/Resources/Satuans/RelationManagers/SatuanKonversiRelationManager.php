<?php

namespace App\Filament\Resources\Satuans\RelationManagers;

use App\Models\Barang;
use App\Models\Satuan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SatuanKonversiRelationManager extends RelationManager
{
    protected static string $relationship = 'konversiDari';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('id_satuan_asal')
                    ->label('Satuan Asal')
                    ->options(Satuan::pluck('nama_satuan', 'id'))
                    ->required()
                    // Mengambil ID parent jika berada di dalam Relation Manager
                    ->default(function ($livewire) {
                        if ($livewire instanceof RelationManager) {
                            return $livewire->getOwnerRecord()->id;
                        }
                        return null;
                    })
                    // Membuatnya tidak bisa diubah
                    ->disabled()
                    // PENTING: dehydrated(true) agar nilai tetap dikirim ke database saat simpan
                    ->dehydrated(),

                Select::make('id_satuan_tujuan')
                    ->label('Satuan Tujuan')
                    ->options(Satuan::pluck('nama_satuan', 'id'))
                    ->required()
                    ->searchable(),

                Select::make('id_barang')
                    ->label('Barang (Opsional)')
                    ->options(Barang::pluck('nama_barang', 'id'))
                    ->searchable()
                    ->placeholder('Pilih Barang jika spesifik'),

                // Text Input Biasa untuk Nilai
                TextInput::make('nilai_konversi')
                    ->label('Nilai Konversi')
                    ->numeric()
                    ->required()
                    ->step(0.000001),

                // Text Input Biasa untuk Keterangan
                TextInput::make('keterangan')
                    ->label('Keterangan'),

                // Date Picker Biasa
                DatePicker::make('berlaku_mulai')
                    ->label('Tanggal Berlaku Mulai')
                    ->default(now())
                    ->required()
                    ->native(false),

                DatePicker::make('berlaku_sampai')
                    ->label('Tanggal Berlaku Sampai'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('SatuanKonversi')
            ->columns([
                TextColumn::make('satuanAsal.nama_satuan')
                    ->label('Tujuan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('satuanTujuan.nama_satuan')
                    ->label('Tujuan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nilai_konversi')
                    ->label('Nilai')
                    ->numeric() // Sesuai presisi 15,6
                    ->sortable(),

                TextColumn::make('barang.nama_barang')
                    ->label('Barang')
                    ->placeholder('Global (Semua Barang)')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('berlaku_mulai')
                    ->label('Mulai')
                    ->date('d/m/Y')
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->visible(true),
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
