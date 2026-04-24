<?php

namespace App\Filament\Resources\JurnalPembantuHeaders\RelationManagers;

use App\Models\JurnalPembantuItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class JurnalPembantuItemRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Detail Item Jurnal';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Identitas Item')
                    ->schema([
                        Grid::make(2)->schema([

                            TextInput::make('urut')
                                ->label('Urutan')
                                ->numeric()
                                ->default(fn() => ($this->ownerRecord->items()->max('urut') ?? 0) + 1)
                                ->required(),

                            Select::make('jenis_pihak')
                                ->label('Jenis Pihak')
                                ->options(JurnalPembantuItem::JENIS_PIHAK)
                                ->nullable()
                                ->native(false),

                            TextInput::make('nama_pihak')
                                ->label('Nama Pihak')
                                ->placeholder('Nama pelanggan / pemasok / karyawan')
                                ->maxLength(255),

                            TextInput::make('nama_barang')
                                ->label('Nama Barang / Produk')
                                ->placeholder('Contoh: Telur Ayam Grade A, Pakan Konsentrat, DOC')
                                ->maxLength(255),

                            TextInput::make('no_dokumen')
                                ->label('No. Dokumen')
                                ->placeholder('No. Invoice / Surat Jalan')
                                ->maxLength(100),

                            TextInput::make('no_referensi')
                                ->label('No. Referensi')
                                ->maxLength(100),

                            Textarea::make('keterangan')
                                ->label('Keterangan')
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),
                    ]),

                Section::make('Kuantitas & Nilai')
                    ->schema([
                        Grid::make(3)->schema([

                            TextInput::make('banyak')
                                ->label('Banyak / Qty')
                                ->numeric()
                                ->step('0.0001')
                                ->required()
                                ->placeholder('Jumlah unit / kg / karton / butir')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Get $get, callable $set) {
                                    $set('jumlah', round((float) $state * (float) $get('harga'), 4));
                                }),

                            TextInput::make('harga')
                                ->label('Harga Satuan (Rp)')
                                ->numeric()
                                ->step('0.0001')
                                ->required()
                                ->prefix('Rp')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Get $get, callable $set) {
                                    $set('jumlah', round((float) $get('banyak') * (float) $state, 4));
                                }),

                            TextInput::make('jumlah')
                                ->label('Jumlah (Rp)')
                                ->numeric()
                                ->prefix('Rp')
                                ->disabled()
                                ->dehydrated(true)
                                ->helperText('Dikalkulasi otomatis: Banyak × Harga'),
                        ]),
                    ]),

                Section::make('Status')
                    ->schema([
                        Toggle::make('status')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Nonaktifkan item tanpa menghapusnya.'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('urut')
            ->columns([

                TextColumn::make('urut')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),

                TextColumn::make('nama_barang')
                    ->label('Nama Barang')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('jenis_pihak')
                    ->label('Jenis Pihak')
                    ->formatStateUsing(fn($state) => JurnalPembantuItem::JENIS_PIHAK[$state] ?? $state)
                    ->placeholder('—'),

                TextColumn::make('nama_pihak')
                    ->label('Nama Pihak')
                    ->searchable()
                    ->limit(20)
                    ->placeholder('—'),

                TextColumn::make('banyak')
                    ->label('Qty')
                    ->numeric(2)
                    ->alignRight(),

                TextColumn::make('harga')
                    ->label('Harga Satuan')
                    ->money('IDR')
                    ->alignRight(),

                TextColumn::make('jumlah')
                    ->label('Jumlah (Rp)')
                    ->money('IDR')
                    ->alignRight()
                    ->weight('bold')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('IDR')),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('status')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Item')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Pastikan jumlah terkalkulasi meski live() belum trigger
                        $data['jumlah']     = round((float) ($data['banyak'] ?? 0) * (float) ($data['harga'] ?? 0), 4);
                        $data['created_by'] = Auth::id();
                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['jumlah']     = round((float) ($data['banyak'] ?? 0) * (float) ($data['harga'] ?? 0), 4);
                        $data['updated_by'] = Auth::id();
                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
