<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    protected $fillable = [
        'tanggal',
    ];

    public function detailPresensi()
    {
        return $this->hasMany(DetailPresensi::class, 'id_presensi');
    }
}
