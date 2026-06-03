<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogKematianAyam extends Model
{
    protected $fillable = [
        'id_ayam',
        'id_kandang',
        'tanggal',
        'jumlah_mati',
        'keterangan',
        'created_by',
        'is_jurnal_created',
    ];

    protected $casts = [
        'tanggal'          => 'date',
        'is_jurnal_created' => 'boolean',
    ];

    public function ayam()
    {
        return $this->belongsTo(Ayam::class, 'id_ayam');
    }

    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'id_kandang');
    }
}
