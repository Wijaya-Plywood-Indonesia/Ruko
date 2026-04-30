<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Komposisi extends Model
{
    protected $fillable = [
        'id_barang'
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function detailKomposisi()
    {
        return $this->hasMany(DetailKomposisi::class, 'id_komposisi');
    }

    // Semua sesi produksi yang menggunakan komposisi ini
    public function produksiPakan()
    {
        return $this->hasMany(ProduksiPakan::class, 'id_komposisi');
    }
}
