<?php

namespace App\Filament\Resources\Pembelis\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PembeliInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nama'),
                TextEntry::make('telepon'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
