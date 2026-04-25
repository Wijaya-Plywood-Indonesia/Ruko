<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduksiPakan extends Model
{
    protected $fillable = [
        'id_komposisi',
        'tanggal_produksi',
        'keterangan',
        'created_by',
        'validated_by'
    ];

    protected $casts = [
        'tanggal_produksi' => 'date',
    ];


    public function komposisi()
    {
        return $this->belongsTo(Komposisi::class, 'id_komposisi');
    }

    // Rincian bahan aktual yang digunakan
    public function produksiPakanBahan()
    {
        return $this->hasMany(ProduksiPakanBahan::class, 'id_produksi_pakan');
    }

    // Hasil akhir produksi (auto-generate saat status = selesai)
    public function produksiPakanHasil()
    {
        return $this->hasOne(ProduksiPakanHasil::class, 'id_produksi_pakan');
    }
}
