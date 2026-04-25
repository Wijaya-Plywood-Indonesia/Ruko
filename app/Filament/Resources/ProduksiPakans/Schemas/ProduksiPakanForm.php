<?php

namespace App\Filament\Resources\ProduksiPakans\Schemas;

use App\Models\DetailKomposisi;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProduksiPakanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal_produksi')
                    ->label('Pilih Tanggal Laporan')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->maxDate(now())
                    ->default(now())
                    ->live()
                    ->closeOnDateSelection()
                    ->suffixIcon('heroicon-o-calendar')
                    ->suffixIconColor('primary')
                    ->required(),

                TextInput::make('created_by')
                    ->label('Dibuat Oleh')
                    // Simpan ID User yang sedang login ke database
                    ->default(fn() => Filament::auth()->id())
                    // Tampilkan Nama Role + Nama User sebagai label bantuan (Visual Saja)
                    ->formatStateUsing(function () {
                        $user = Filament::auth()->user();
                        if (!$user) return 'Tidak diketahui';

                        // Langsung mengambil nama user agar lebih mudah dicek
                        return $user->name;
                    })
                    ->disabled()
                    ->dehydrated(),

                Select::make('id_komposisi')
                    ->label('Pilih Resep / Komposisi Pakan')
                    ->relationship(
                        name: 'komposisi',
                        titleAttribute: 'id',
                        /** * Load relasi barang agar nama produk muncul di dropdown
                         */
                        modifyQueryUsing: fn($query) => $query->with('barang')
                    )
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        $namaProduk = $record->barang?->nama_barang ?? 'Produk Tanpa Nama';
                        return "{$namaProduk}";
                    })
                    ->searchable()
                    ->preload()
                    ->live() // Aktifkan reaktivitas
                    ->required()
                    ->afterStateUpdated(function (Set $set, $state) {
                        /**
                         * LOGIKA AUTO-FILL BAHAN
                         * Jika Anda menggunakan Repeater dengan nama 'produksiPakanBahan' di form ini,
                         * kode di bawah akan otomatis mengisi baris bahannya.
                         */
                        if (!$state) return;

                        $details = DetailKomposisi::where('id_komposisi', $state)->get();

                        $bahanAuto = $details->map(fn($d) => [
                            'id_barang' => $d->id_barang,
                            'kuantitas' => $d->kuantitas,
                            'keterangan' => 'Sesuai Resep',
                        ])->toArray();

                        // Isi field 'produksiPakanBahan' (Jika ada komponen Repeater di form ini)
                        $set('produksiPakanBahan', $bahanAuto);
                    }),
            ]);
    }
}
