<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\IdentitasToko;
use App\Models\Kategori;
use App\Models\Pegawai;
use App\Models\Pembeli;
use App\Models\Satuan;
use App\Models\User;
use App\Models\ListAkun;
use App\Models\IndukAkun;
use App\Models\AnakAkun;
use App\Models\SubAnakAkun;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']));

        // 1. Identitas Toko
        $tokos = [];
        for ($i = 1; $i <= 5; $i++) {
            $tokos[] = IdentitasToko::create([
                'kode_toko' => "TK" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'nama_toko' => "Toko Wijaya $i",
                'alamat' => "Jl. Raya No. $i, Jakarta",
                'telepon' => "0812345678$i",
                'email' => "toko$i@wijaya.com",
                'status' => 'aktif',
            ]);
        }

        // 2. Pegawai
        $pegawais = [];
        for ($i = 1; $i <= 5; $i++) {
            $pegawais[] = Pegawai::create([
                'nik' => "320123456789000$i",
                'nama_lengkap' => "Pegawai Lengkap $i",
                'nama_panggilan' => "Pegawai $i",
                'jenis_kelamin' => $i % 2 == 0 ? 'P' : 'L',
                'telepon' => "0857123456$i",
                'alamat' => "Alamat Pegawai $i",
                'status' => 'AKTIF',
            ]);
        }

        // 3. Pembeli
        $pembelis = [];
        for ($i = 1; $i <= 5; $i++) {
            $pembelis[] = Pembeli::create([
                'nama' => "Customer $i",
                'telepon' => "0821987654$i",
                'alamat' => "Alamat Customer $i",
                'email' => "customer$i@gmail.com",
            ]);
        }

        // 4. Satuan
        $satuans = ['Pcs', 'Box', 'Kg', 'Liter', 'Meter'];
        $satuanList = [];
        foreach ($satuans as $s) {
            $satuanList[] = Satuan::create(['nama_satuan' => $s]);
        }

        // 5. Kategori
        $kategoris = ['Makanan', 'Minuman', 'Elektronik', 'Pakaian', 'Lainnya'];
        $kategoriIds = [];
        foreach ($kategoris as $k) {
            $kategoriIds[] = Kategori::create(['nama_kategori' => $k])->id;
        }

        // 6. Barang
        $barangList = [];
        for ($i = 1; $i <= 15; $i++) {
            $barangList[] = Barang::create([
                'kode_barang' => "BRG-" . str_pad($i, 5, '0', STR_PAD_LEFT),
                'barcode' => "899123456" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'nama_barang' => "Barang Contoh $i",
                'id_kategori' => $kategoriIds[array_rand($kategoriIds)],
                'id_satuan' => $satuanList[array_rand($satuanList)]->id,
                'harga_beli' => rand(1000, 50000),
                'harga_jual' => rand(60000, 150000),
                'stok_minimum' => 10,
                'is_active' => true,
            ]);
        }

        // 7. ListAkun (Linking User 2 (Admin) to Toko and Pegawai)
        $admin = User::find(2);
        if ($admin) {
            ListAkun::create([
                'id_akun' => $admin->id,
                'id_pegawai' => $pegawais[0]->id,
                'id_toko' => $tokos[0]->id,
            ]);
        }

        // 8. Accounting Hierarchy
        $induks = [
            ['1', 'Aset', 'debit'],
            ['2', 'Kewajiban', 'kredit'],
            ['3', 'Ekuitas', 'kredit'],
            ['4', 'Pendapatan', 'kredit'],
            ['5', 'Beban', 'debit'],
        ];

        foreach ($induks as $induk) {
            $i = IndukAkun::create([
                'kode_induk_akun' => $induk[0],
                'nama_induk_akun' => $induk[1],
                'saldo_normal' => $induk[2],
                'status' => 'aktif',
            ]);

            for ($j = 1; $j <= 2; $j++) {
                $a = AnakAkun::create([
                    'id_induk_akun' => $i->id,
                    'kode_anak_akun' => $i->kode_induk_akun . "." . $j,
                    'nama_anak_akun' => "Anak " . $induk[1] . " $j",
                    'status' => 'aktif',
                ]);

                for ($k = 1; $k <= 2; $k++) {
                    SubAnakAkun::create([
                        'id_anak_akun' => $a->id,
                        'kode_sub_anak_akun' => $a->kode_anak_akun . "." . $k,
                        'nama_sub_anak_akun' => "Sub Anak " . $a->nama_anak_akun . " $k",
                        'status' => 'aktif',
                    ]);
                }
            }
        }

        // 9. Penjualan
        for ($i = 1; $i <= 5; $i++) {
            $customer = $pembelis[array_rand($pembelis)];
            $penjualan = Penjualan::create([
                'no_nota' => "NOTA-" . date('Ymd') . "-" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'tanggal' => Carbon::now()->subDays(rand(0, 10)),
                'nama_customer' => $customer->nama,
                'alamat' => $customer->alamat,
                'metode_pembayaran' => $i % 2 == 0 ? 'TRANSFER' : 'TUNAI',
                'user_id' => $admin ? $admin->id : 1,
                'toko_id' => $tokos[0]->id,
                'status_transaksi' => 'VALIDATED',
                'total' => 0,
                'bayar' => 0,
                'kembalian' => 0,
            ]);

            $total = 0;
            for ($j = 1; $j <= 3; $j++) {
                $barang = $barangList[array_rand($barangList)];
                $qty = rand(1, 5);
                $subtotal = $qty * $barang->harga_jual;
                $total += $subtotal;

                DetailPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $barang->id,
                    'nama_barang' => $barang->nama_barang,
                    'satuan' => $barang->satuan->nama_satuan ?? 'Pcs',
                    'qty' => $qty,
                    'harga_awal' => $barang->harga_beli,
                    'harga_jual' => $barang->harga_jual,
                    'subtotal' => $subtotal,
                ]);
            }

            $penjualan->update([
                'total' => $total,
                'bayar' => $total,
                'kembalian' => 0,
            ]);
        }
    }
}
