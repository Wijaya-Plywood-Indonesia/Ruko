<?php

namespace App\Filament\Resources\JurnalPembantuHeaders\Schemas;

use App\Models\JurnalPembantuHeader;
use App\Models\SubAnakAkun;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JurnalPembantuHeaderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Identitas Jurnal')
                ->schema([
                    Grid::make(2)->schema([

                        TextInput::make('no_jurnal_pembantu')
                            ->label('No. Jurnal Pembantu')
                            ->numeric()
                            ->required()
                            ->default(fn() => (JurnalPembantuHeader::max('no_jurnal_pembantu') ?? 0) + 1)
                            ->helperText('Di-generate otomatis, bisa diubah jika perlu.'),

                        TextInput::make('jurnal')
                            ->label('No. Jurnal Umum')
                            ->numeric()
                            ->required()
                            ->default(fn() => (JurnalPembantuHeader::max('jurnal') ?? 0) + 1)
                            ->helperText('Satu nomor jurnal = pasangan Debet & Kredit.'),

                        DatePicker::make('tgl_transaksi')
                            ->label('Tgl. Transaksi')
                            ->default(now())
                            ->nullable()
                            ->helperText('Kosongkan jika sama dengan tanggal pencatatan.'),

                        Select::make('jenis_transaksi')
                            ->label('Jenis Transaksi')
                            ->options(JurnalPembantuHeader::JENIS)
                            ->required()
                            ->native(false),

                        Select::make('modul_asal')
                            ->label('Modul Asal')
                            ->options(JurnalPembantuHeader::MODUL_ASAL)
                            ->nullable()
                            ->native(false),

                        TextInput::make('no_dokumen')
                            ->label('No. Dokumen')
                            ->placeholder('No. Invoice / Surat Jalan / PO')
                            ->maxLength(100)
                            ->nullable(),
                    ]),
                ]),

            Section::make('Akun & Posisi')
                ->schema([
                    Grid::make(2)->schema([

                        Select::make('no_akun')
                            ->label('Akun')
                            ->options(
                                fn() => SubAnakAkun::where('status', 'aktif')
                                    ->orderBy('kode_sub_anak_akun')
                                    ->get()
                                    ->mapWithKeys(fn($a) => [
                                        $a->kode_sub_anak_akun => "{$a->kode_sub_anak_akun} — {$a->nama_sub_anak_akun}"
                                    ])
                            )
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $akun = SubAnakAkun::where('kode_sub_anak_akun', $state)->first();
                                $set('nama_akun', $akun?->nama_sub_anak_akun);
                            }),

                        Select::make('map')
                            ->label('Posisi D/K')
                            ->options(JurnalPembantuHeader::MAP)
                            ->required()
                            ->native(false),

                        Hidden::make('nama_akun'),
                    ]),
                ]),

            Section::make('Keterangan')
                ->schema([
                    Textarea::make('keterangan')
                        ->label('Keterangan')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('catatan_internal')
                        ->label('Catatan Internal')
                        ->rows(2)
                        ->nullable()
                        ->helperText('Tidak diteruskan ke Jurnal Umum.')
                        ->columnSpanFull(),
                ]),

            Hidden::make('dibuat_oleh')
                ->default(fn() => Filament::auth()->id())
                ->dehydrated(),
        ]);
    }
}
