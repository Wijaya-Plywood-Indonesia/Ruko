<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduksiTelur extends Model
{
    protected $fillable = [
        'id_kandang',
        'tanggal',
        'jumlah_telur_butir',
        'jumlah_telur_retak',
        'jumlah_telur_pecah',
        'hen_day_production',
        'created_by',
        'is_validated',
        'validated_by',
        'validated_at',
        'keterangan',
    ];

    protected $casts = [
        'tanggal'       => 'date',
        'validated_at'  => 'datetime',
    ];

    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'id_kandang');
    }


    /**
     * Telur layak jual = butir - retak - afkir
     */
    public function getTelurBaikAttribute(): int
    {
        return max(0, $this->jumlah_telur_butir
            - $this->jumlah_telur_retak
            - $this->jumlah_telur_pecah);
    }


    /**
     * Hen Day Production = (telur hari ini / jumlah ayam saat ini) * 100
     */
    public function hitungHDP(int $jumlahTelur): float
    {
        if ($this->jumlah_saat_ini <= 0) return 0;
        return round(($jumlahTelur / $this->jumlah_saat_ini) * 100, 2);
    }

    public function getHdpBadgeColorAttribute(): string
    {
        return match (true) {
            $this->hen_day_production > 80  => 'success',
            $this->hen_day_production >= 70 => 'warning',
            $this->hen_day_production >= 60 => 'info',
            default                         => 'danger',
        };
    }
}
