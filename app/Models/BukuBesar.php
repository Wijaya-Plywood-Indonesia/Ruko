<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BukuBesar extends Model
{
    protected $table = 'buku_besar';
    protected $fillable = [
        'bulan',
        'tahun',
        'no_akun',
        'nama_akun',
        'saldo',
    ];

    public function subAkun()
    {
        return $this->belongsTo(
            SubAnakAkun::class,
            'no_akun',
            'kode_sub_anak_akun'
        );
    }
    public function anakAkun()
    {
        return $this->subAkun?->anakAkun();
    }

    public function indukAkun()
    {
        return $this->subAkun?->indukAkun();
    }
}
