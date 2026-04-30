<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduksiPakanHasil extends Model
{
    protected $fillable = [
        'id_produksi_pakan',
        'id_barang',
        'kuantitas',
        'keterangan',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
    public function produksiPakan()
    {
        return $this->belongsTo(ProduksiPakan::class, 'id_produksi_pakan');
    }
}
