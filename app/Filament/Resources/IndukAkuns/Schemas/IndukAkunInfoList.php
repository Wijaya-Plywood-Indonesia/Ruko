<?php

namespace App\Filament\Resources\IndukAkuns\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IndukAkunInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                Section::make('Informasi Induk Akun')
                    ->schema([
                        Grid::make(3)->schema([

                            TextEntry::make('kode_induk_akun')
                                ->label('Kode'),

                            TextEntry::make('nama_induk_akun')
                                ->label('Nama'),

                            TextEntry::make('anakAkuns_count')
                                ->label('Jumlah Anak Akun')
                                ->state(
                                    fn($record) =>
                                    $record->anakAkuns->count()
                                )
                                ->badge()
                                ->color('primary'),
                        ]),
                    ]),
                /* ================== LIST ANAK ================== */

                Section::make('Daftar Anak Akun')
                    ->schema([

                        RepeatableEntry::make('anakAkuns')
                            ->label('')
                            ->schema([

                                Grid::make(2)->schema([

                                    TextEntry::make('kode_anak_akun')
                                        ->label('Kode'),

                                    TextEntry::make('nama_anak_akun')
                                        ->label('Nama'),
                                ]),

                                /* ==== JUMLAH SUB ==== */

                                TextEntry::make('sub_count')
                                    ->label('Jumlah Sub Anak')
                                    ->state(
                                        fn($record) =>
                                        $record->subAnakAkuns->count()
                                    )
                                    ->badge()
                                    ->color('success'),

                                /* ==== LIST SUB ==== */

                                RepeatableEntry::make('subAnakAkuns')
                                    ->label('Sub Anak Akun')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextEntry::make('kode_sub_anak_akun')
                                                ->label('Kode'),

                                            TextEntry::make('nama_sub_anak_akun')
                                                ->label('Nama'),
                                        ])
                                    ])
                                    ->columns(1)
                                    ->visible(
                                        fn($record) =>
                                        $record->subAnakAkuns->count() > 0
                                    ),
                            ])
                            ->columns(1)
                            ->visible(
                                fn($record) =>
                                $record->anakAkuns->count() > 0
                            ),
                    ])
            ]);
    }
}
