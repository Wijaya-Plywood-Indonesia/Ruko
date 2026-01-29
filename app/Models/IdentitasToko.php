<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentitasToko extends Model
{
    //
    protected $table = 'identitas_toko'; // sesuaikan jika nama tabel berbeda

    protected $fillable = [
        'kode_toko',
        'nama_toko',
        'pemilik',
        'alamat',
        'telepon',
        'email',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'status' => 'string',
    ];
    public function stokBarang()
    {
        return $this->hasMany(StokBarangToko::class, 'toko_id');
    }
}
