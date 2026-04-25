<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarangMasuk extends Model
{

    protected $fillable = [
        'tanggal',
        'penerima_barang',
        'nomor_nota',
        'created_by',
        'validated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function detailBarangMasuks(): HasMany
    {
        return $this->hasMany(DetailBarangMasuk::class, 'id_barang_masuk');
    }
}
