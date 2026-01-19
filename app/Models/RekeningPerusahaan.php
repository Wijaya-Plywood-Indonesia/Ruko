<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekeningPerusahaan extends Model
{
    //
    protected $table = 'rekening_perusahaan';

    protected $fillable = [
        'pemilik_rekening',
        'nama_bank',
        'no_rekening',
        'atas_nama',
    ];

}
