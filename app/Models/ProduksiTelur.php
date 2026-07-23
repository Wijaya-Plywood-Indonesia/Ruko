<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduksiTelur extends Model
{
    /**
     * Konversi 1 Peti = 10 Kg
     */
    public const PETI_TO_KG = 10;

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

        // Hasil Kandang
        'hasil_peti',
        'hasil_kiloan',
        'hasil_sisa',
        'hasil_bentes',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'validated_at' => 'datetime',

        'hasil_peti' => 'decimal:2',
        'hasil_kiloan' => 'decimal:2',
        'hasil_sisa' => 'decimal:2',
        'hasil_bentes' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'id_kandang');
    }

    public function details()
    {
        return $this->hasMany(DetailProduksiTelur::class, 'id_produksi_telur');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor
    |--------------------------------------------------------------------------
    */

    /**
     * Telur layak jual
     */
    public function getTelurBaikAttribute(): int
    {
        return max(
            0,
            $this->jumlah_telur_butir
                - $this->jumlah_telur_retak
                - $this->jumlah_telur_pecah
        );
    }

    /**
     * Total hasil kandang dalam Kg
     */
    public function getHasilTotalKgAttribute(): float
    {
        return round(
            ($this->hasil_peti * self::PETI_TO_KG)
                + $this->hasil_kiloan
                + $this->hasil_sisa
                + $this->hasil_bentes,
            2
        );
    }

    /**
     * Total kilo dari seluruh detail kandang
     */
    public function getDariKandangKgAttribute(): float
    {
        if ($this->relationLoaded('details')) {
            return round(
                $this->details->sum('jumlah_telur_kilo'),
                2
            );
        }

        return round(
            $this->details()->sum('jumlah_telur_kilo'),
            2
        );
    }

    /**
     * Selisih hasil kandang dengan total input kandang
     */
    public function getSelisihKgAttribute(): float
    {
        return round(
            $this->dari_kandang_kg - $this->hasil_total_kg,
            2
        );
    }

    /**
     * Hen Day Production
     */
    public function hitungHDP(int $jumlahTelur): float
    {
        if ($this->jumlah_saat_ini <= 0) {
            return 0;
        }

        return round(($jumlahTelur / $this->jumlah_saat_ini) * 100, 2);
    }

    /**
     * Badge warna HDP
     */
    public function getHdpBadgeColorAttribute(): string
    {
        return match (true) {
            $this->hen_day_production > 80 => 'success',
            $this->hen_day_production >= 70 => 'warning',
            $this->hen_day_production >= 60 => 'info',
            default => 'danger',
        };
    }
}
