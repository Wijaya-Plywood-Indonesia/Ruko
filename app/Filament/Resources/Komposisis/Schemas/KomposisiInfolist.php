<?php

namespace App\Filament\Resources\Komposisis\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class KomposisiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id_barang.nama_barang')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
