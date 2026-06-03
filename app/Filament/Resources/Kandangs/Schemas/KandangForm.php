<?php

namespace App\Filament\Resources\Kandangs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class KandangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_kandang')
                    ->required(),
                Textarea::make('keterangan')
                    ->label("Keterangan"),
                Toggle::make('is_aktif')
                    ->required(),
            ]);
    }
}
