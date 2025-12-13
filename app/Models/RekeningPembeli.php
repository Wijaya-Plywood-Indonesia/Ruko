<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekeningPembeli extends Model
{
    //
    protected $fillable = [
        'pembeli_id',
        'jenis',
        'nama_bank',
        'nama_ewallet',
        'no_rekening',
        'atas_nama',
    ];

    /** ============================
     *  RELASI
     *  ============================ */

    // Rekening milik satu pembeli
    public function pembeli()
    {
        return $this->belongsTo(Pembeli::class);
    }
}
