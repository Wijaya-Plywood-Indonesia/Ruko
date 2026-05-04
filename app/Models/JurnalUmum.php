<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurnalUmum extends Model
{
    // Inisiasi table 
    protected $guarded = [];

    protected $fillable = [
        'tgl',
        'jurnal',
        'no_akun',
        'nama_akun',
        'map',  
        'nama',
        'banyak',
        'harga',
        'keterangan',
    ];

    // Database Casting
    protected $casts = [
        'tgl' => 'date',
        'jurnal' => 'integer',
        'no_akun' => 'string',
        'banyak' => 'decimal:4',
        'harga' => 'decimal:2',
    ];

    // RelationShip
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

    // Perhitungan Debit dan Kredit
    public function getDebitAttribute()
    {
        return in_array(strtolower($this->map), ['d', 'debit'])
            ? $this->nilai
            : 0;
    }

    public function getKreditAttribute()
    {
        return in_array(strtolower($this->map), ['k', 'kredit'])
            ? $this->nilai
            : 0;
    }
}
