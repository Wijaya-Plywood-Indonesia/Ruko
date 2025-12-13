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
}
