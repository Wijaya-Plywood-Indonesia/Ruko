<?php

namespace App\Filament\Resources\Ayams\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AyamInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('kandang.nama_kandang')
                    ->label('Kandang'),
                TextEntry::make('nama_batch')
                    ->label('Nama Batch'),
                TextEntry::make('tanggal_masuk')
                    ->date()
                    ->label('Tanggal Masuk'),
                TextEntry::make('jumlah_awal')
                    ->numeric()
                    ->label('Jumlah Ayam Masuk'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->label('Dibuat Pada'),
                TextEntry::make('usia')
                    ->label('Usia Saat Masuk')
                    ->suffix(' hari'),
                TextEntry::make('umur_format')
                    ->label('Usia Sekarang')
                    ->state(fn($record) => $record->umur_format)
                    ->badge()
                    ->color(fn($record): string => match (true) {
                        $record->umur_hari < 30  => 'info',
                        $record->umur_hari < 90  => 'success',
                        $record->umur_hari < 180 => 'warning',
                        default                  => 'danger',
                    }),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->label('Diperbarui Pada'),
            ]);
    }
}
