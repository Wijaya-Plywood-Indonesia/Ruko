<?php

namespace App\Filament\Resources\Penjualans\RelationManagers;

use App\Filament\Traits\SyncBarangSatuan;
use App\Models\Barang;
use App\Services\StokPenyesuaianService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';
    public function isReadOnly(): bool
    {
        return false;
    }
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
                            ->relationship(
                                'barang',
                                'nama_barang',
                                modifyQueryUsing: function (Builder $query) {
                                    $user = auth()->user();
                                    $tokoUser = $user->tokoUtama()->first();

                                    if (!$tokoUser) {
                                        $query->whereRaw('1 = 0');
                                        return;
                                    }

                                    $barangQuery = StokPenyesuaianService::queryBarangByToko($tokoUser->id_toko);

                                    // inject query service ke relationship
                                    $query->fromSub($barangQuery, 'barangs');
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->reactive() // 🔥 penting
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                if (!$state) {
                                    return;
                                }

                                $barang = Barang::with('satuan')->find($state);

                                $set('satuan', $barang?->satuan?->nama_satuan ?? '-');
                                $set('harga_awal', $barang->harga_jual ?? 0);
                                $set('harga_jual', $barang->harga_jual ?? 0);

                                $set(
                                    'subtotal',
                                    StokPenyesuaianService::calculate_subtotal(
                                        $barang->harga_jual,
                                        $get('qty') ?? 1,
                                        $get('potongan') ?? 0
                                    )
                                );
                            })
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
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->afterStateUpdated(
                                fn($get, $set) =>
                                $set(
                                    'subtotal',
                                    StokPenyesuaianService::calculate_subtotal(
                                        $get('harga_jual'),
                                        $get('qty'),
                                        $get('potongan')
                                    )
                                )
                            ),

                        TextInput::make('harga_jual')
                            ->label('Harga Jual')
                            ->numeric()
                            ->prefix('Rp')
                            ->dehydrated()
                            ->required()
                            ->afterStateUpdated(
                                fn($get, $set) =>
                                $set(
                                    'subtotal',
                                    StokPenyesuaianService::calculate_subtotal(
                                        $get('harga_jual'),
                                        $get('qty'),
                                        $get('potongan')
                                    )
                                )
                            ),
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
                            ->default(
                                function () {
                                    $penjualan = $this->getOwnerRecord();
                                    return $penjualan && $penjualan->is_member != true ? 0 : 5000;
                                }
                            )
                            ->afterStateUpdated(
                                fn($get, $set) =>
                                $set(
                                    'subtotal',
                                    StokPenyesuaianService::calculate_subtotal(
                                        $get('harga_jual'),
                                        $get('qty'),
                                        $get('potongan')
                                    )
                                )
                            ),



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
                CreateAction::make()
                    ->visible(function () {
                        $penjualan = $this->getOwnerRecord();

                        if (!$penjualan) {
                            return false;
                        }

                        return $penjualan->status_transaksi !== 'LUNAS';
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        try {
                            $barang = Barang::find($data['barang_id']);
                            $penjualan = $this->getOwnerRecord();

                            $data['penjualan_id'] = $penjualan->id;
                            $data['nama_barang'] = $barang->nama_barang;
                            $data['harga_awal'] = $barang->harga_jual;
                            $data['qty'] = (int) $data['qty'];

                            StokPenyesuaianService::validateSubtotal(
                                $data['harga_jual'] ?? 0,
                                $data['qty'] ?? 0,
                                $data['potongan'] ?? 0
                            );
                        } catch (ValidationException $e) {

                            Notification::make()
                                ->title('Pembelian Tidak Wajar')
                                ->body('Subtotal hasil perhitungan kurang dari atau sama dengan 0 maupun potongan yang tidak wajar')
                                ->danger()
                                ->send();

                            throw $e; // ❗ penting: hentikan submit
                        }

                        return $data;
                    }),
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
