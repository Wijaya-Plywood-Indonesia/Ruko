<?php

namespace App\Filament\Resources\Supliers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SupliersInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nama')
                    ->label('Nama Supplier'),

                TextEntry::make('telepon')
                    ->label('Telepon'),

                TextEntry::make('email')
                    ->label('Email'),

                TextEntry::make('npwp')
                    ->label('NPWP'),

                TextEntry::make('alamat')
                    ->label('Alamat')
                    ->columnSpanFull(),

                TextEntry::make('keterangan_tambahan')
                    ->label('Keterangan Tambahan')
                    ->columnSpanFull(),

                IconEntry::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextEntry::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i'),

                TextEntry::make('updated_at')
                    ->label('Diupdate Pada')
                    ->dateTime('d M Y H:i'),
                //
            ]);
    }
}
