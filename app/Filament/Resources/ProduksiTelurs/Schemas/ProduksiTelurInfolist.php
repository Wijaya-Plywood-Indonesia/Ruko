<?php

namespace App\Filament\Resources\ProduksiTelurs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProduksiTelurInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id_kandang')
                    ->numeric(),
                TextEntry::make('id_ayam')
                    ->numeric(),
                TextEntry::make('tanggal')
                    ->date(),
                TextEntry::make('jumlah_telur_butir')
                    ->numeric(),
                TextEntry::make('jumlah_telur_retak')
                    ->numeric(),
                TextEntry::make('jumlah_telur_pecah')
                    ->numeric(),
                TextEntry::make('jumlah_ayam_mati')
                    ->numeric(),
                TextEntry::make('hen_day_production')
                    ->numeric(),
                TextEntry::make('created_by'),
                TextEntry::make('validated_by'),
                TextEntry::make('validated_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
