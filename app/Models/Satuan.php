<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Satuan extends Model
{
    //
    protected $table = 'satuans';

    protected $fillable = [
        'nama_satuan',
        'keterangan',
        'is_base_unit'
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relasi
    |--------------------------------------------------------------------------
    */

    public function barangs()
    {
        return $this->hasMany(Barang::class, 'id_satuan');
    }

    public function konversiDari()
    {
        return $this->hasMany(SatuanKonversi::class, 'id_satuan_asal');
    }

    public function konversiKe()
    {
        return $this->hasMany(SatuanKonversi::class, 'id_satuan_tujuan');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    public function konversiAktif()
    {
        return $this->konversiDari()->where('berlaku_mulai', '<=', now())
            ->where(function ($query) {
                $query->whereNull('berlaku_sampai')
                    ->orWhere('berlaku_sampai', '>=', now());
            });
    }
}
