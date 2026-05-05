<?php

namespace App\Filament\Resources\RekeningPerusahaans\Schemas;

use App\Models\SubAnakAkun;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RekeningPerusahaanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('pemilik_rekening')
                    ->label('Pemilik Rekening')
                    ->maxLength(255),

                TextInput::make('nama_bank')
                    ->label('Nama Bank')
                    ->placeholder('BCA, BRI, Mandiri, OVO, DANA')
                    ->maxLength(255),

                TextInput::make('no_rekening')
                    ->label('Nomor Rekening / E-Wallet')
                    ->maxLength(255),

                TextInput::make('atas_nama')
                    ->label('Atas Nama')
                    ->maxLength(255),

                // ── Mapping ke Akun Jurnal ────────────────────────────────────
                Select::make('sub_anak_akun_id')
                    ->label('Akun Jurnal (untuk Transfer)')
                    ->helperText('Akun kas/bank yang akan di-debit saat pembayaran transfer ke rekening ini.')
                    ->searchable()
                    ->nullable()
                    ->options(
                        fn() => SubAnakAkun::orderBy('kode_sub_anak_akun')
                            ->get()
                            ->mapWithKeys(fn($a) => [
                                $a->id => "{$a->kode_sub_anak_akun} — {$a->nama_sub_anak_akun}"
                            ])
                    )
                    ->getSearchResultsUsing(
                        fn(string $search) => SubAnakAkun::where('kode_sub_anak_akun', 'like', "%{$search}%")
                            ->orWhere('nama_sub_anak_akun', 'like', "%{$search}%")
                            ->limit(30)
                            ->get()
                            ->mapWithKeys(fn($a) => [
                                $a->id => "{$a->kode_sub_anak_akun} — {$a->nama_sub_anak_akun}"
                            ])
                    )
                    ->getOptionLabelUsing(
                        fn($value) => SubAnakAkun::find($value) !== null
                            ? SubAnakAkun::find($value)->kode_sub_anak_akun . ' — ' . SubAnakAkun::find($value)->nama_sub_anak_akun
                            : null
                    ),
            ]);
    }
}