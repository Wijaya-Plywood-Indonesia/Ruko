<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ayam extends Model
{
    protected $fillable = [
        'id_kandang',
        'nama_batch',
        'tanggal_masuk',
        'jumlah_awal',
        'usia'
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
    ];

    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'id_kandang');
    }

    public function logKematians()
    {
        return $this->hasMany(LogKematianAyam::class, 'id_ayam');
    }

    // public function produksiTelurs()
    // {
    //     return $this->hasMany(ProduksiTelur::class, 'id_ayam');
    // }

    // Umur dalam hari
    public function getUmurHariAttribute(): int
    {
        $selisihHari = (int) $this->tanggal_masuk->diffInDays(now());
        return $this->usia + $selisihHari;
    }

    // Umur format tampilan
    public function getUmurFormatAttribute(): string
    {
        $hari = $this->umur_hari;

        if ($hari < 30) {
            return "{$hari} hari";
        }

        $bulan = intdiv($hari, 30);
        $sisa  = $hari % 30;

        return $sisa === 0
            ? "{$bulan} bulan"
            : "{$bulan} bulan {$sisa} hari";
    }
}
