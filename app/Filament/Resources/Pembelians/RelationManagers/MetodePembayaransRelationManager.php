<?php

namespace App\Filament\Resources\Pembelians\RelationManagers;

use App\Models\PembelianMetodePembayaran;
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

class MetodePembayaransRelationManager extends RelationManager
{


    public function isReadOnly(): bool
    {
        return false;
    }

    protected static string $relationship = 'metodePembayarans';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options(PembelianMetodePembayaran::labelMetode())
                    ->required()
                    ->native(false),

                TextInput::make('amount')
                    ->label('Jumlah Bayar')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                DatePicker::make('tanggal_bayar')
                    ->label('Tanggal Bayar')
                    ->default(now())
                    ->required(),

                TextInput::make('reference_number')
                    ->label('No. Referensi/Bukti')
                    ->maxLength(255),

                Textarea::make('catatan')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Pembayaran')
            ->columns([
                TextColumn::make('tanggal_bayar')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->formatStateUsing(fn(string $state): string => PembelianMetodePembayaran::labelMetode()[$state] ?? $state)
                    ->badge()
                    ->color('info'),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('reference_number')
                    ->label('Ref #')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('createdBy.name')
                    ->label('Input Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
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
