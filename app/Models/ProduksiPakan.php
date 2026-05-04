<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduksiPakan extends Model
{
    protected $fillable = [
        'tanggal_produksi',
        'keterangan',
        'created_by',
        'validated_by'
    ];

    protected $casts = [
        'tanggal_produksi' => 'date',
    ];

    // Relasi ke yang lainnya
    public function pakanMentahs()
    {
        return $this->hasMany(ProduksiPakanMentah::class, 'id_produksi_pakan');
    }

    public function pakanCampurans()
    {
        return $this->hasMany(ProduksiPakanCampuran::class, 'id_produksi_pakan');
    }
}
