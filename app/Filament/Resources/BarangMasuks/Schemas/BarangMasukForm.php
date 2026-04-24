<?php

namespace App\Filament\Resources\BarangMasuks\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BarangMasukForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal')
                    ->label('Tanggal Masuk')
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

                TextInput::make('penerima_barang')
                    ->label('Penerima Barang')
                    ->required(),

                TextInput::make('nomor_nota')
                    ->label('Nomor Nota')
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
            ]);
    }
}
