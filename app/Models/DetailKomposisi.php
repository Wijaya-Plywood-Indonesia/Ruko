<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailKomposisi extends Model
{
    protected $fillable = [
        'id_komposisi',
        'id_barang',
        'kuantitas',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function komposisi()
    {
        return $this->belongsTo(Komposisi::class, 'id_komposisi');
    }
}
