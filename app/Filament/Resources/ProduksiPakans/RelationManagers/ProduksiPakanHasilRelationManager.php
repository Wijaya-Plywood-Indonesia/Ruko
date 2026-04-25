<?php

namespace App\Filament\Resources\ProduksiPakans\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProduksiPakanHasilRelationManager extends RelationManager
{
    protected static string $relationship = 'produksiPakanHasil';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // TextInput::make('kuantitas')
                //     ->numeric()
                //     ->live()
                //     ->required(),

                // Textarea::make('keterangan')
                //     ->label('Keterangan')
                //     ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ProduksiPakanHasil')
            ->columns([
                TextColumn::make('barang.nama_barang')
                    ->label('Barang / Bahan (Satuan)')
                    ->formatStateUsing(function ($record) {
                        if (!$record->barang) return '—';

                        $namaBarang = $record->barang->nama_barang;
                        $satuan = $record->barang->satuan?->nama_satuan ?? '-';

                        // Menghasilkan format: "Jagung Giling (Kg)"
                        return "{$namaBarang} ({$satuan})";
                    })
                    /**
                     * PENCARIAN GANDA (Nama & Satuan)
                     * Memungkinkan user mencari "Jagung" atau mencari "Kg" langsung di kolom yang sama.
                     */
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereHas('barang', function ($q) use ($search) {
                            $q->where('nama_barang', 'like', "%{$search}%")
                                ->orWhereHas('satuan', function ($sq) use ($search) {
                                    $sq->where('nama_satuan', 'like', "%{$search}%");
                                });
                        });
                    }),

                TextColumn::make('kuantitas')
                    ->alignCenter(),

                TextColumn::make('ket')
                    ->label('Keterangan')
                    ->default('-')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // CreateAction::make(),
            ])
            ->recordActions([
                // EditAction::make(),
                // DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
