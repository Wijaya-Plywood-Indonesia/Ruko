<?php

namespace App\Filament\Resources\Pegawais\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class PegawaiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Pegawai')
                    ->description('Informasi pribadi dan data dasar pegawai.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextEntry::make('nik')->label('NIK'),
                        TextEntry::make('nama_lengkap')->label('Nama Lengkap'),
                        TextEntry::make('nama_panggilan')->label('Nama Panggilan'),
                        TextEntry::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->formatStateUsing(fn($state) => $state === 'L' ? 'Laki-laki' : 'Perempuan'),
                        TextEntry::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->date('d M Y'),
                        TextEntry::make('tanggal_masuk')
                            ->label('Tanggal Masuk')
                            ->date('d M Y'),
                    ])
                    ->columns(2),

                Section::make('Kontak & Alamat')
                    ->description('Data kontak dan alamat pegawai.')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextEntry::make('telepon')->label('Telepon'),
                        TextEntry::make('email')->label('Email'),
                        TextEntry::make('alamat')->label('Alamat'),
                    ])
                    ->columns(2),

                Section::make('Foto Pegawai & KTP')
                    ->description('Dokumentasi foto pegawai dan identitas.')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        TextEntry::make('foto_pegawai')
                            ->label('Foto Pegawai')
                            ->formatStateUsing(fn($state) => $state ? 'Lihat Foto' : '-')
                            ->url(
                                fn($record) => $record->foto_pegawai
                                ? Storage::url($record->foto_pegawai)
                                : null
                            )
                            ->openUrlInNewTab()
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('foto_ktp')
                            ->label('Foto KTP')
                            ->formatStateUsing(fn($state) => $state ? 'Lihat Foto' : '-')
                            ->url(
                                fn($record) => $record->foto_ktp
                                ? Storage::url($record->foto_ktp)
                                : null
                            )
                            ->openUrlInNewTab()
                            ->badge()
                            ->color('success'),
                    ])
                    ->columns(2),

                Section::make('Status Kepegawaian')
                    ->description('Informasi status aktif atau nonaktif.')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state) => $state === 'AKTIF' ? 'success' : 'danger'),
                    ]),
            ]);
    }
}
