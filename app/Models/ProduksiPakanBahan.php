<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduksiPakanBahan extends Model
{
    protected $fillable = [
        'id_produksi_pakan',
        'id_barang',
        'kuantitas',
        'keterangan',
    ];

    public function produksiPakan()
    {
        return $this->belongsTo(ProduksiPakan::class, 'id_produksi_pakan');
    }

    /**
     * Relasi ke Master Barang (Material Pakan).
     * Menggunakan 'id_barang' sebagai foreign key.
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
