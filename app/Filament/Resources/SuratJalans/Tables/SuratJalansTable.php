<?php

namespace App\Filament\Resources\SuratJalans\Tables;

use App\Models\SuratJalan;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuratJalansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_surat_jalan')
                    ->searchable(),

                TextColumn::make('tanggal_kirim')
                    ->date()
                    ->sortable(),

                TextColumn::make('tokoAsal.nama_toko')
                    ->label('Dari'),
                TextColumn::make('tokoTujuan.nama_toko')
                    ->label('Ke'),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'dikirim',
                        'success' => 'diterima',
                        'danger' => 'ditolak',
                    ]),
                TextColumn::make('createdBy.name')
                    ->label("Pembuat")
                    ->sortable(),
                TextColumn::make('validatedBy.name')
                    ->label("Validator")
                    ->sortable(),

                TextColumn::make('nama_supir')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('jeniskendaraan')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('plat')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
                Filter::make('tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(now()->subWeek()),
                        DatePicker::make('until')
                            ->default(now())
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn(Builder $query, $date) =>
                                $query->whereDate('tanggal_kirim', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn(Builder $query, $date) =>
                                $query->whereDate('tanggal_kirim', '<=', $date)
                            );
                    }),
            ])
            //     ->filtersLayout(FiltersLayout::AboveContent)
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn(SuratJalan $record) => $record->status === 'draft'),
                DeleteAction::make()
                    ->visible(fn(SuratJalan $record) => $record->status === 'draft'),

                Action::make('cetak')
                    ->label('Cetak Surat Jalan')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->url(fn($record) => route('surat-jalan.gudang.cetak', $record->id))
                    ->openUrlInNewTab()
                // ->visible(
                //     fn($record) =>
                //     !empty($record->validated_by)
                //     && ($record->isDikirim() || $record->isDiterima())
                //  )
                ,
            ])
            ->toolbarActions([
            ]);
    }
}
