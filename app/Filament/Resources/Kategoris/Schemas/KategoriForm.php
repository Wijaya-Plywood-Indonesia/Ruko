<?php

namespace App\Filament\Resources\Kategoris\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KategoriForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_kategori')
                    ->required()
                    ->maxLength(255),
                Select::make('parent_id')
                    ->label('Parent Kategori')
                    ->relationship('parent', 'nama_kategori')
                    ->searchable()
                    ->placeholder('Kategori Utama')
                    ->nullable(),
            ]);
    }
}
