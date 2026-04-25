<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPresensi extends Model
{
    protected $fillable = [
        'id_presensi',
        'id_pegawai',
        'jam_masuk',
        'jam_pulang',
        'ijin',
        'keterangan',
        'hasil',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai');
    }

    public function presensi()
    {
        return $this->belongsTo(Presensi::class, 'id_presensi');
    }
}
