<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProduksiPakan;

class ProduksiPakanCampuran extends Model
{
    protected $fillable = [
        'id_produksi_pakan',
        'id_barang',
        'stok_awal',
        'masuk',
        'keluar_pullet',
        'keluar_l1',
        'keluar_l2',
        'stok_akhir',
        'keterangan',
    ];

    protected $casts = [
        'stok_awal'     => 'integer',
        'masuk'         => 'integer',
        'keluar_pullet' => 'integer',
        'keluar_l1'     => 'integer',
        'keluar_l2'     => 'integer',
        'stok_akhir'    => 'integer',
    ];

    // Relation Manager 
    public function produksiPakan()
    {
        return $this->belongsTo(ProduksiPakan::class, 'id_produksi_pakan');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
