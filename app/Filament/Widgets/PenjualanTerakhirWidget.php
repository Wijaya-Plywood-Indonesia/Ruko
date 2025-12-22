<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Penjualan;
use Illuminate\Support\Facades\Auth;

class PenjualanTerakhirWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => Penjualan::query())
            ->recordUrl(null) // ⬅️ INI PENTING
            ->columns([
                //
                TextColumn::make('no_nota')
                    ->label('Nota')
                    ->searchable(),

                TextColumn::make('status_transaksi')
                    ->badge()
                    ->searchable(),
                TextColumn::make('nama_customer')
                    ->searchable()
                    ->placeholder('Tidak Dicatat'),
                TextColumn::make('metode_pembayaran')
                // /->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('validator.name')
                    ->label('Validator')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('validasi_transaksi')
                    ->label('Validasi Transaksi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')

                    // Hanya tampil jika BELUM divalidasi
                    ->visible(fn($record) => empty($record->validated_by))

                    // Kasir TIDAK boleh validasi
                    ->disabled(fn($record) => Auth::id() === $record->user_id)

                    ->modalHeading('Validasi Transaksi')
                    ->modalSubmitActionLabel('Simpan Validasi')

                    ->form([
                        TextInput::make('validator_name')
                            ->label('Validator')
                            ->default(Auth::user()->name)
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('status_transaksi')
                            ->label('Status Transaksi')
                            ->options([
                                'LUNAS' => 'LUNAS',
                                'COD' => 'COD',
                                'PENDING' => 'PENDING',
                                'DIBATALKAN' => 'DIBATALKAN',
                            ])
                            ->required(),
                    ])

                    ->action(function ($record, array $data) {
                        // Safety check backend
                        if (Auth::id() === $record->user_id) {
                            throw new \Exception('Kasir tidak boleh memvalidasi transaksinya sendiri.');
                        }

                        $record->update([
                            'validated_by' => Auth::id(),
                            'status_transaksi' => $data['status_transaksi'],
                        ]);
                    }),

                //
                Action::make('cetak')
                    ->label('Nota')
                    ->icon('heroicon-o-printer')
                    ->url(fn($record) => route('nota.cetak', $record))
                    ->openUrlInNewTab(),
                Action::make('suratJalan')
                    ->label('Surat Jalan')
                    ->icon('heroicon-o-truck')
                    ->url(fn($record) => route('surat-jalan.cetak', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
