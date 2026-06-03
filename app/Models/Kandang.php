<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kandang extends Model
{
    protected $fillable = [
        'nama_kandang',
        'is_aktif',
        'keterangan',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
    ];

    // Satu kandang satu batch aktif
    public function ayam()
    {
        return $this->hasMany(Ayam::class, 'id_kandang');
    }

    public function produksiTelurs()
    {
        return $this->hasMany(ProduksiTelur::class, 'id_kandang');
    }

    public function logKematians()
    {
        return $this->hasMany(LogKematianAyam::class, 'id_kandang');
    }

    public function getTerisiAttribute(): bool
    {
        return $this->ayam()->exists();
    }
}
