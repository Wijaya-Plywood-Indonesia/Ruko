<?php

namespace App\Filament\Resources\Pembelis\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RekeningPembeliRelationManager extends RelationManager
{
    protected static string $relationship = 'rekening';
    protected static ?string $title = 'Daftar Rekening';
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('jenis')
                    ->label('Jenis')
                    ->options([
                        'BANK' => 'Bank',
                        'EWALLET' => 'E-Wallet',
                    ])
                    ->required()
                    ->reactive(),
                TextInput::make('nama_bank')
                    ->label('Nama Bank')
                    ->visible(fn($get) => $get('jenis') === 'BANK'),
                TextInput::make('nama_ewallet')
                    ->label('Nama E-Wallet')
                    ->visible(fn($get) => $get('jenis') === 'EWALLET'),
                TextInput::make('no_rekening')
                    ->label('Nomor Rekening / No HP')
                    ->required(),
                TextInput::make('atas_nama')
                    ->label('Atas Nama')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table

            ->columns([
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state === 'BANK' ? 'Bank' : 'E-Wallet')
                    ->color(fn($state) => $state === 'BANK' ? 'primary' : 'success'),

                TextColumn::make('nama_bank')
                    ->hidden(fn($record) => $record?->jenis !== 'BANK'),

                TextColumn::make('nama_ewallet')
                    ->hidden(fn($record) => $record?->jenis !== 'EWALLET'),

                TextColumn::make('no_rekening')
                    ->label('NoRek'),

                TextColumn::make('atas_nama'),
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
