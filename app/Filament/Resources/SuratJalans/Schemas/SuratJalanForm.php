<?php

namespace App\Filament\Resources\SuratJalans\Schemas;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\DB;
use Filament\Schemas\Schema;

class SuratJalanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('created_by')
                    ->default(fn() => auth()->id()),

                TextInput::make('no_surat_jalan')
                    ->label('No Surat Jalan')
                    ->disabled()
                    ->dehydrated(true)
                    ->default(function (callable $get) {
                        $tanggal = $get('tanggal_kirim') ?? now()->toDateString();
                        $datePart = Carbon::parse($tanggal)->format('Ymd');

                        $count = DB::table('surat_jalan')
                            ->whereDate('tanggal_kirim', $tanggal)
                            ->count();

                        $next = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

                        return "SJ-{$datePart}-{$next}";
                    }),
                DatePicker::make('tanggal_kirim')
                    ->default(now())
                    ->required(),

                Select::make('toko_asal_id')
                    ->label('Dikirim Dari')
                    ->relationship(
                        'tokoAsal',
                        'nama_toko',
                        modifyQueryUsing: fn($query) => $query->where('status', 'aktif')
                    )
                    ->required(),
                Select::make('toko_tujuan_id')
                    ->label('Penerima')
                    ->relationship(
                        'tokoTujuan',
                        'nama_toko',
                        fn($query) => $query->where('status', 'aktif')
                    )
                    ->required()
                    ->different('toko_asal_id')
                    ->validationMessages([
                        'different' => 'Pengirim dan Penerima tidak boleh sama.',
                        'required' => 'Toko tujuan wajib dipilih.',
                    ]),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                    ])
                    ->default('draft')
                    ->required(),

                TextInput::make('nama_supir')->label("Nama Supir"),
                TextInput::make('jeniskendaraan')
                    ->label("Jenis Kendaraan"),
                TextInput::make('plat')->label("No Polisi"),

                Textarea::make('keterangan')
                    ->columnSpanFull(),


            ]);
    }
}
