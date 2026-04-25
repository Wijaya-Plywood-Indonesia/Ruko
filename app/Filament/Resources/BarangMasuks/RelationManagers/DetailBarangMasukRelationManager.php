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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
                    ->required()
                    ->columnSpan(2),

                TextInput::make('kuantitas')
                    ->label('Kuantitas')
                    ->numeric()
                    ->default(1)
                    ->live()
                    /**
                     * LOGIKA REAKTIF:
                     * Diperbarui untuk mengecek status record.
                     */
                    ->afterStateUpdated(function (Get $get, Set $set, ?Model $record) {
                        // JIKA SEDANG EDIT ($record tidak null), HENTIKAN AUTO-CALCULATE
                        if ($record) {
                            return;
                        }

                        $qty = (float) ($get('kuantitas') ?? 0);
                        $harga = (float) ($get('harga_satuan') ?? 0);
                        $set('sub_total', $qty * $harga);
                    })
                    ->required(),

                TextInput::make('harga_satuan')
                    ->label('Harga Satuan')
                    ->numeric()
                    ->prefix('Rp')
                    ->live()
                    /**
                     * LOGIKA REAKTIF:
                     * Diperbarui untuk mengecek status record agar sub_total tidak berubah saat edit.
                     */
                    ->afterStateUpdated(function (Get $get, Set $set, ?Model $record) {
                        // JIKA SEDANG EDIT, JANGAN UBAH SUB_TOTAL
                        if ($record) {
                            return;
                        }

                        $qty = (float) ($get('kuantitas') ?? 0);
                        $harga = (float) ($get('harga_satuan') ?? 0);
                        $set('sub_total', $qty * $harga);
                    })
                    ->required(),

                TextInput::make('sub_total')
                    ->label('Sub Total')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    /**
                     * PENGUNCIAN DATA:
                     * Field ini tetap ReadOnly saat Edit dan nilai tidak akan berubah 
                     * karena diproteksi di fungsi afterStateUpdated di atas.
                     */
                    ->readOnly(fn($record) => $record !== null)
                    ->dehydrated()
                    ->live(),

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
                    ->label('Detail Barang Masuk')
                    ->visible(
                        fn(RelationManager $livewire) =>
                        Auth::user()->hasRole('super_admin') ||
                            $livewire->getOwnerRecord()->validated_by === null
                    ),
            ])
            ->recordActions([
                EditAction::make()->visible(
                    fn(RelationManager $livewire) =>
                    Auth::user()->hasRole('super_admin') ||
                        $livewire->getOwnerRecord()->validated_by === null
                ),
                DeleteAction::make()->visible(
                    fn(RelationManager $livewire) =>
                    Auth::user()->hasRole('super_admin') ||
                        $livewire->getOwnerRecord()->validated_by === null
                ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(
                        fn(RelationManager $livewire) =>
                        Auth::user()->hasRole('super_admin') ||
                            $livewire->getOwnerRecord()->validated_by === null
                    ),
                ]),
            ]);
    }
}
