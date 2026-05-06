<?php

namespace App\Filament\Resources\Pembelians\Schemas;

use App\Models\Pembelian;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PembeliansInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 
                Section::make('Informasi Pembelian')
                    ->schema([
                        TextEntry::make('nomor_nota')
                            ->label('Nomor Nota'),

                        TextEntry::make('tanggal')
                            ->label('Tanggal')
                            ->date('d M Y'),

                        TextEntry::make('supplier_name')
                            ->label('Supplier'),

                        TextEntry::make('supplier_phone')
                            ->label('Telepon'),

                        TextEntry::make('supplier_npwp')
                            ->label('NPWP'),

                        TextEntry::make('supplier_address')
                            ->label('Alamat')
                            ->columnSpanFull(),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn(string $state) => Pembelian::labelStatus()[$state] ?? $state)
                            ->color(fn(string $state) => Pembelian::warnaBadgeStatus()[$state] ?? 'gray'),

                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull(),
                        TextEntry::make('createdBy.name')
                            ->label('Dibuat Oleh'),

                        TextEntry::make('validatedBy.name')
                            ->label('Divalidasi Oleh')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make('Nominal')
                    ->schema([
                        TextEntry::make('sub_total')
                            ->label('Sub Total')
                            ->money('IDR'),

                        TextEntry::make('total_diskon')
                            ->label('Diskon')
                            ->money('IDR'),

                        TextEntry::make('total_ppn')
                            ->label('PPN')
                            ->money('IDR'),

                        TextEntry::make('ongkir')
                            ->label('Ongkir')
                            ->money('IDR'),

                        TextEntry::make('biaya_lain')
                            ->label('Biaya Lain')
                            ->money('IDR'),

                        TextEntry::make('grand_total')
                            ->label('Grand Total')
                            ->money('IDR'),
                    ])
                    ->columns(3),

                Section::make('Foto Nota')
                    ->schema([
                        ImageEntry::make('foto')
                            ->hiddenLabel(),
                    ]),


            ]);
    }
}
