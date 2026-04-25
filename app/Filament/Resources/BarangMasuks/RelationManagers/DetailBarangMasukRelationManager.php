<?php

namespace App\Filament\Resources\BarangMasuks\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DetailBarangMasukRelationManager extends RelationManager
{
    protected static string $relationship = 'detailBarangMasuks';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('id_barang')
                    ->label('Pilih Barang')
                    ->relationship('barang', 'nama_barang') // Mengasumsikan tabel barang punya kolom 'nama'
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(2),

                TextInput::make('kuantitas')
                    ->numeric()
                    ->default(1)
                    ->live()
                    ->required(),

                TextInput::make('harga_satuan')
                    ->label('Harga Satuan')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                TextInput::make('sub_total')
                    ->label('Sub Total')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),

                // Logging Pembuat Detail
                TextInput::make('created_by')
                    ->label('Diinput Oleh')
                    ->default(fn() => Auth::user()?->name)
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id_barang')
            ->columns([
                TextColumn::make('barang.nama_barang')
                    ->label('Nama Barang')
                    ->searchable(),

                TextColumn::make('kuantitas')
                    ->alignCenter(),

                TextColumn::make('harga_satuan')
                    ->label('Harga')
                    ->money('IDR') // Format mata uang Rupiah
                    ->sortable(),

                TextColumn::make('sub_total')
                    ->label('Total')
                    ->money('IDR'),

                TextColumn::make('created_by')
                    ->label('User')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Detail Barang Masuk'),
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
