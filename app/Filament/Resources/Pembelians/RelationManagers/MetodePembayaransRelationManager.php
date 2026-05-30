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
use Filament\Schemas\Components\Utilities\Get;
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
                    ->options(function () {
                        $metode = PembelianMetodePembayaran::labelMetode();
                        unset($metode[PembelianMetodePembayaran::METODE_LAINNYA]);

                        return $metode;
                    })
                    ->required()
                    ->live(),

                TextInput::make('amount')
                    ->label('Jumlah Bayar')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->live(debounce: 500) // Penting agar state terupdate secara real-time
                    ->helperText(function ($state, $record, $livewire, $get) {
                        // 1. Ambil data induk
                        $pembelianId = $record?->pembelian_id ?? $livewire->getOwnerRecord()->id;

                        // 2. Hitung Sisa
                        $grandTotal = \App\Models\Pembelian::find($pembelianId)->grand_total ?? 0;
                        $totalSudahDibayar = \App\Models\PembelianMetodePembayaran::where('pembelian_id', $pembelianId)
                            ->where('id', '!=', $record?->id)
                            ->sum('amount');

                        $sisaBayar = $grandTotal - $totalSudahDibayar;
                        $inputAmount = (float) $state;

                        // 3. Logika Pesan
                        if ($inputAmount > $sisaBayar) {
                            return "Peringatan: Nilai melebihi sisa tagihan! (Sisa: Rp " . number_format($sisaBayar, 0, ',', '.') . ")";
                        }

                        return "Sisa yang harus dibayar: Rp " . number_format($sisaBayar, 0, ',', '.');
                    })
                    // Tambahkan style agar terlihat seperti peringatan saat melebihi batas
                    ->extraInputAttributes(function ($state, $record, $livewire) {
                        $pembelianId = $record?->pembelian_id ?? $livewire->getOwnerRecord()->id;
                        $grandTotal = \App\Models\Pembelian::find($pembelianId)->grand_total ?? 0;
                        $totalSudahDibayar = \App\Models\PembelianMetodePembayaran::where('pembelian_id', $pembelianId)
                            ->where('id', '!=', $record?->id)->sum('amount');

                        if ((float) $state > ($grandTotal - $totalSudahDibayar)) {
                            return ['style' => 'border-color: #ef4444; color: #ef4444;']; // Warna merah
                        }
                        return [];
                    }),

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
                    ->sortable()
                    ->summarize(
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Bayar')
                            ->money('IDR')
                            // Tambahkan logika hidden agar tidak muncul jika data kosong
                            ->hidden(fn(\Illuminate\Database\Eloquent\Builder $query): bool => ! $query->exists())
                    ),

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
