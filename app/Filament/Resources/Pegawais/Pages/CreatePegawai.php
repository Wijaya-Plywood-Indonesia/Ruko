<?php

namespace App\Filament\Resources\Pegawais\Pages;

use App\Filament\Resources\Pegawais\PegawaiResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Pegawai;
use App\Models\User;
use App\Models\ListAkun;
use App\Models\IdentitasToko;
use Filament\Notifications\Notification;

class CreatePegawai extends CreateRecord
{
    protected static string $resource = PegawaiResource::class;

    protected function handleRecordCreation(array $data): Pegawai
    {
        return DB::transaction(function () use ($data) {

            // ======================
            // ambil field akun
            // ======================
            $buatAkun = $data['buat_akun'] ?? false;
            $username = $data['akun_username'] ?? null;
            $role = $data['akun_role'] ?? null;
            $idToko = $data['id_toko'] ?? null;

            unset(
                $data['buat_akun'],
                $data['akun_username'],
                $data['akun_role'],
                $data['id_toko'], // penting: pegawai tidak punya toko
            );

            // ======================
            // VALIDASI TOKO
            // ======================
            if (!$idToko) {
                throw new \Exception('Toko wajib dipilih.');
            }

            // ======================
            // create pegawai
            // ======================
            $pegawai = Pegawai::create($data);

            // kalau tidak buat akun selesai
            if (!$buatAkun) {
                return $pegawai;
            }

            // ======================
            // ambil toko
            // ======================
            $toko = IdentitasToko::find($idToko);

            if (!$toko) {
                throw new \Exception('Toko tidak ditemukan.');
            }

            // ======================
            // generate email
            // ======================
            $nama = Str::slug($pegawai->nama_lengkap, '.');
            $domain = Str::slug($toko->nama_toko);

            $email = "{$nama}@{$domain}.com";

            // ======================
            // generate password
            // ======================
            $passwordPlain = Str::random(10);

            // ======================
            // create user
            // ======================
            $user = User::create([
                'name' => $username ?: $pegawai->nama_lengkap,
                'email' => $email,
                'password' => Hash::make($passwordPlain),
            ]);

            // ======================
            // assign role
            // ======================
            if ($role) {
                $user->assignRole($role);
            }

            // ======================
            // create list akun
            // ======================
            ListAkun::create([
                'id_pegawai' => $pegawai->id,
                'id_akun' => $user->id,
                'id_toko' => $idToko,
            ]);

            // ======================
            // notif password
            // ======================
            Notification::make()
                ->title('Akun berhasil dibuat')
                ->body("Email : {$email}\nPassword : {$passwordPlain}")
                ->success()
                ->send();

            return $pegawai;
        });
    }
}
