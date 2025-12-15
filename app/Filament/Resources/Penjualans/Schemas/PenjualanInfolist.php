<?php

namespace App\Filament\Resources\Penjualans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PenjualanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('no_nota'),
                TextEntry::make('tanggal')
                    ->dateTime(),
                TextEntry::make('nama_customer'),
                TextEntry::make('metode_pembayaran'),
                TextEntry::make('bank'),
                TextEntry::make('no_rekening'),
                TextEntry::make('kendaraan'),
                TextEntry::make('nama_sopir'),
                TextEntry::make('total')
                    ->numeric(),
                TextEntry::make('bayar')
                    ->numeric(),
                TextEntry::make('kembalian')
                    ->numeric(),
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
