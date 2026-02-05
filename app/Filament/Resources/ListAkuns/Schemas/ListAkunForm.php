<?php

namespace App\Filament\Resources\ListAkuns\Schemas;

use Spatie\Permission\Models\Role;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\User;
use App\Models\ListAkun;


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


            Select::make('role')
                ->label('Jabatan')
                ->options(function (Get $get) {
                    $idPegawai = $get('id_pegawai');

                    if (!$idPegawai) {
                        return Role::pluck('name', 'id');
                    }

                    /**
                     * Ambil semua akun milik pegawai
                     */
                    $akunIds = ListAkun::where('id_pegawai', $idPegawai)
                        ->pluck('id_akun');

                    /**
                     * Ambil role yang sudah dipakai oleh akun-akun tersebut
                     */
                    $usedRoleIds = User::whereIn('id', $akunIds)
                        ->with('roles')
                        ->get()
                        ->pluck('roles.*.id')
                        ->flatten()
                        ->unique()
                        ->toArray();

                    /**
                     * Tampilkan hanya role yang BELUM dipakai
                     */
                    return Role::whereNotIn('id', $usedRoleIds)
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->live()
            //->required()
            ,

            // ===============================
            // Akun (Filtered by Role)
            // ===============================

            Select::make('id_akun')
                ->label('Akun')
                ->disabled(fn(Get $get) => blank($get('role')))
                ->options(function (Get $get) {
                    $roleId = $get('role');

                    if (!$roleId) {
                        return [];
                    }

                    return User::query()
                        ->whereHas('roles', fn($q) => $q->where('roles.id', $roleId))
                        ->pluck('email', 'id');
                })
                ->searchable()
                ->required()
                ->live(),

            // ===============================
            // Toko
            // ===============================
            Select::make('id_toko')
                ->label('Penempatan Toko')
                ->relationship('toko', 'nama_toko', fn($query) => $query->where('status', 'aktif'))
                ->searchable()
                ->preload()
                ->required(),
        ]);
    }
}
