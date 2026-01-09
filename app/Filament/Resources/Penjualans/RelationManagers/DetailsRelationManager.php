<?php

namespace App\Filament\Resources\Penjualans\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                /* ======================================================
             | DATA BARANG
             ====================================================== */
                Section::make('Data Barang')
                    ->columns(1)
                    ->components([

                        Select::make('barang_id')
                            ->label('Barang')
                            ->relationship('barang', 'nama_barang')
                            ->searchable()
                            ->required(),

                        TextInput::make('satuan')
                            ->label('Satuan')
                            ->disabled()
                            ->dehydrated(),
                    ]),

                /* ======================================================
                 | QTY & HARGA
                 ====================================================== */
                Section::make('Qty & Harga')
                    ->columns(1)
                    ->components([

                        TextInput::make('qty')
                            ->label('Qty')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        TextInput::make('harga_awal')
                            ->label('Harga Awal')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),

                        TextInput::make('harga_jual')
                            ->label('Harga Jual')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                    ]),

                /* ======================================================
                 | POTONGAN & SUBTOTAL
                 ====================================================== */
                Section::make('Potongan & Subtotal')
                    ->columns(1)
                    ->components([

                        TextInput::make('potongan')
                            ->label('Potongan')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),

                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),
                    ]),

                /* ======================================================
                 | KETERANGAN
                 ====================================================== */
                Section::make('Keterangan')
                    ->components([

                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(4)
                            ->placeholder('Tambahkan catatan jika ada...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('no_nota')
            ->columns([

                TextColumn::make('barang.nama_barang')
                    ->label('Barang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('satuan')
                    ->label('Satuan')
                    ->alignCenter(),

                TextColumn::make('qty')
                    ->label('Qty')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('harga_awal')
                    ->label('Harga Awal')
                    ->money('IDR', locale: 'id')
                    ->alignRight(),

                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->money('IDR', locale: 'id')
                    ->alignRight(),

                TextColumn::make('potongan')
                    ->label('Potongan')
                    ->money('IDR', locale: 'id')
                    ->placeholder('0')
                    ->alignRight(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR', locale: 'id')
                    ->alignRight()
                    ->weight('bold'),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->placeholder('Tidak Ada')
                    ->tooltip(fn($state) => $state)
                    ->wrap(),


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
