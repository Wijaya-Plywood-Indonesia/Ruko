<?php

namespace App\Filament\Resources\Pembelis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PembelisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->searchable(),

                TextColumn::make('nik')
                    ->formatStateUsing(function ($state) {
                        if (strlen($state) <= 4) {
                            return $state;
                        }

                        return '***' . substr($state, -4);
                    })
                    ->searchable(),

                TextColumn::make('telepon')
                    // ->formatStateUsing(fn($state) => substr($state, -4))
                    ->formatStateUsing(function ($state) {
                        $length = strlen($state);

                        // Nomor terlalu pendek → tampilkan apa adanya
                        if ($length <= 6) {
                            return $state;
                        }

                        // Nomor lokal (kode area, bukan HP)
                        if (str_starts_with($state, '0') && !str_starts_with($state, '08')) {
                            $prefix = substr($state, 0, 4);     // kode area
                            $suffix = substr($state, -4);       // 4 digit terakhir
                            $masked = str_repeat('*', max(0, $length - 8));

                            return $prefix . $masked . $suffix;
                        }

                        // Nomor HP
                        $prefix = substr($state, 0, 4);
                        $suffix = substr($state, -4);
                        $masked = str_repeat('*', max(0, $length - 8));

                        return $prefix . $masked . $suffix;
                    })

                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email address')
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
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
