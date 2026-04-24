<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameDetail extends Model
{
    protected $table = 'stock_opname_details';

    protected $fillable = [
        'stock_opname_id',
        'barang_id',
        'stok_sistem',
        'stok_aktual',
        'selisih',
        'catatan',
    ];

    protected $casts = [
        'stok_sistem' => 'float',
        'stok_aktual' => 'float',
        'selisih' => 'float',
    ];

    /* =========================
     |  RELATIONSHIPS
     ========================= */

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    /* =========================
     |  HELPERS
     ========================= */

    public function hitungSelisih(): float
    {
        return (float) $this->stok_aktual - (float) $this->stok_sistem;
    }

    public function isLebih(): bool
    {
        return $this->hitungSelisih() > 0;
    }

    public function isKurang(): bool
    {
        return $this->hitungSelisih() < 0;
    }

    public function isSesuai(): bool
    {
        return $this->hitungSelisih() === 0.0;
    }
}