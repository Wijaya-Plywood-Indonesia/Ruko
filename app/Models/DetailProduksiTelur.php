<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailProduksiTelur extends Model
{
    protected $fillable = [
        'id_produksi_telur',
        'id_kandang',
        'id_produksi_pakan_campuran',
        'jumlah_telur_butir',
        'jumlah_telur_kilo',
        'jumlah_telur_tray',
    ];

    protected $casts = [
        'jumlah_telur_butir' => 'integer',
        'jumlah_telur_kilo'  => 'decimal:2',
        'jumlah_telur_tray'  => 'integer',
    ];

    // ─── Relations ───────────────────────────────────────────

    public function produksiTelur()
    {
        return $this->belongsTo(ProduksiTelur::class, 'id_produksi_telur');
    }

    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'id_kandang');
    }

    public function pakanCampuran()
    {
        return $this->belongsTo(ProduksiPakanCampuran::class, 'id_produksi_pakan_campuran');
    }

    // ─── Computed ────────────────────────────────────────────

    /**
     * Total butir + tray dalam satu baris (jika dibutuhkan untuk display).
     */
    public function getTotalAttribute(): array
    {
        return [
            'butir' => $this->jumlah_telur_butir,
            'kilo'  => $this->jumlah_telur_kilo,
            'tray'  => $this->jumlah_telur_tray,
        ];
    }
}
