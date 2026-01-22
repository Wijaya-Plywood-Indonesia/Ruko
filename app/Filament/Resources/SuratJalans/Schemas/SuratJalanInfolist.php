<?php

namespace App\Filament\Resources\SuratJalans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SuratJalanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('no_surat_jalan'),
                TextEntry::make('tanggal_kirim')
                    ->date(),
                TextEntry::make('toko_asal_id')
                    ->numeric(),
                TextEntry::make('toko_tujuan_id')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('nama_supir'),
                TextEntry::make('jeniskendaraan'),
                TextEntry::make('plat'),
                TextEntry::make('created_by')
                    ->numeric(),
                TextEntry::make('validated_by')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
