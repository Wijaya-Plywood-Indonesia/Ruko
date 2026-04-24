<?php

namespace App\Filament\Resources\JurnalPembantuHeaders\Schemas;

use App\Models\JurnalPembantuHeader;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JurnalPembantuHeaderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Identitas Jurnal')
                ->schema([
                    Grid::make(3)->schema([

                        TextEntry::make('no_jurnal_pembantu')
                            ->label('No. Jurnal Pembantu'),

                        TextEntry::make('jurnal')
                            ->label('No. Jurnal Umum'),

                        TextEntry::make('tgl_transaksi')
                            ->label('Tgl. Transaksi')
                            ->date('d/m/Y')
                            ->placeholder('—'),

                        TextEntry::make('jenis_transaksi')
                            ->label('Jenis Transaksi')
                            ->formatStateUsing(fn($state) => JurnalPembantuHeader::JENIS[$state] ?? $state),

                        TextEntry::make('modul_asal')
                            ->label('Modul Asal')
                            ->placeholder('—'),

                        TextEntry::make('no_dokumen')
                            ->label('No. Dokumen')
                            ->placeholder('—'),
                    ]),
                ]),

            Section::make('Akun & Posisi')
                ->schema([
                    Grid::make(3)->schema([

                        TextEntry::make('no_akun')
                            ->label('Kode Akun'),

                        TextEntry::make('nama_akun')
                            ->label('Nama Akun'),

                        TextEntry::make('map')
                            ->label('Posisi D/K')
                            ->badge()
                            ->color(fn($state) => $state === 'd' ? 'info' : 'warning')
                            ->formatStateUsing(fn($state) => $state === 'd' ? 'Debet' : 'Kredit'),

                        TextEntry::make('total_nilai')
                            ->label('Total Nilai')
                            ->money('IDR'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'draft' => 'gray',
                                'diposting' => 'success',
                                'dibalik' => 'warning',
                                'dibatalkan' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn($state) => JurnalPembantuHeader::STATUSES[$state] ?? $state),

                        TextEntry::make('tgl_posting')
                            ->label('Tgl. Posting')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Belum diposting'),
                    ]),
                ]),

            Section::make('Keterangan')
                ->schema([
                    TextEntry::make('keterangan')
                        ->label('Keterangan')
                        ->columnSpanFull(),

                    TextEntry::make('catatan_internal')
                        ->label('Catatan Internal')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            Section::make('Audit Trail')
                ->collapsed()
                ->schema([
                    Grid::make(3)->schema([

                        TextEntry::make('dibuatOleh.name')
                            ->label('Dibuat Oleh'),

                        TextEntry::make('diubahOleh.name')
                            ->label('Terakhir Diubah Oleh')
                            ->placeholder('—'),

                        TextEntry::make('dipostingOleh.name')
                            ->label('Diposting Oleh')
                            ->placeholder('—'),

                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diubah Pada')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('membalik_id')
                            ->label('Membalik Header ID')
                            ->placeholder('—'),
                    ]),
                ]),
        ]);
    }
}
