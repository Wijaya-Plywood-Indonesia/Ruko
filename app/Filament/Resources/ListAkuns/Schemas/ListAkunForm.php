<?php

namespace App\Filament\Resources\ListAkuns\Schemas;

use Spatie\Permission\Models\Role;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\User;


class ListAkunForm
{
    public static function configure(Schema $schema): Schema
    {
return $schema->components([
            // ===============================
            // Nama Pegawai
            // ===============================
            Select::make('id_pegawai')
                ->label('Nama Pegawai')
                ->relationship('pegawai', 'nama_lengkap')
                ->searchable()
                ->preload()
                ->required(),

            // ===============================
            // Jabatan
            // ===============================

Select::make('roles')
    ->label('Jabatan')
    ->options(
        Role::query()->pluck('name', 'id')
    )
    ->searchable()
    ->preload()
    ->live()
    ->required(),

            // ===============================
            // Akun (Filtered by Role)
            // ===============================

Select::make('id_akun')
    ->label('Akun')
    ->options(fn (Get $get) =>
        User::query()
            ->when(
                $get('roles'),
                fn ($q, $roleId) =>
                    $q->whereHas(
                        'roles',
                        fn ($qr) => $qr->where('roles.id', $roleId)
                    )
            )
            ->pluck('email', 'id')
    )
    ->searchable()
    ->required(),

            // ===============================
            // Toko
            // ===============================
            Select::make('id_toko')
                ->label('Penempatan Toko')
                ->relationship('toko', 'nama_toko')
                ->searchable()
                ->preload()
                ->required(),
        ]);    }
}
