<?php

namespace App\Filament\Resources\Komposisis\RelationManagers;

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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DetailKomposisiRelationManager extends RelationManager
{
    protected static string $relationship = 'detailKomposisi';

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
                    ->relationship(
                        name: 'barang',
                        titleAttribute: 'nama_barang',
                        /** * Eager load 'satuan' agar query efisien saat memunculkan dropdown
                         */
                        modifyQueryUsing: fn(Builder $query) => $query->with('satuan')
                    )
                    ->getOptionLabelFromRecordUsing(function (Model $record) {
                        $satuan = $record->satuan?->nama_satuan ?? '-';
                        return "{$record->nama_barang} ({$satuan})";
                    })
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('kuantitas')
                    ->numeric()
                    ->live()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('detailKomposisi')
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
