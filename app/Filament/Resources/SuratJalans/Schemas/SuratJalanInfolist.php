<?php

namespace App\Filament\Resources\SuratJalans\Schemas;

use Carbon\Carbon;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SuratJalanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Informasi Surat Jalan')
                    ->description('Detail utama pengiriman barang')
                    ->columns(2)
                    ->schema([

                        TextEntry::make('no_surat_jalan')
                            ->label('No Surat Jalan')
                            ->weight('bold'),

                        TextEntry::make('alur_pengiriman')
                            ->wrap()
                            ->label('Pengiriman')
                            ->state(function ($record) {
                                $asal = $record->tokoAsal?->nama_toko ?? '-';
                                $tujuan = $record->tokoTujuan?->nama_toko ?? '-';

                                $tanggal = $record->tanggal_kirim
                                    ? Carbon::parse($record->tanggal_kirim)->translatedFormat('d M Y')
                                    : '-';

                                return "Dari {$asal} dikirim ke {$tujuan} pada {$tanggal}";
                            })
                            ->columnSpanFull(),

                        TextEntry::make('info_pengirim')
                            ->label('Pengirim')
                            ->state(function ($record) {
                                $supir = $record->nama_supir ?? '-';
                                $kendaraan = $record->jeniskendaraan ?? 'kendaraan tidak diketahui';
                                $plat = $record->plat ?? '-';

                                return "🚚 Dikirim oleh {$supir} mobil {$kendaraan} - ({$plat})";
                            })
                            ->placeholder('-'),

                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn($state) => strtoupper($state))
                            ->colors([
                                'secondary' => 'draft',
                                'warning' => 'dikirim',
                                'success' => 'diterima',
                                'danger' => 'ditolak',
                            ]),



                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                Section::make('Informasi Sistem')
                    ->schema([
                        TextEntry::make('createdBy.name')
                            ->label('Dibuat Oleh'),

                        TextEntry::make('validatedBy.name')
                            ->label('Divalidasi Oleh')
                            ->placeholder('Belum Validasi'),

                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Terakhir Diubah')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

            ]);
    }
}
