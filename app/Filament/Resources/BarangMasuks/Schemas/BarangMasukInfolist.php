<?php

namespace App\Filament\Resources\BarangMasuks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BarangMasukInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tanggal')
                    ->date(),
                TextEntry::make('penerima_barang'),
                TextEntry::make('nomor_nota'),
                TextEntry::make('created_by'),
                TextEntry::make('validated_by'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
