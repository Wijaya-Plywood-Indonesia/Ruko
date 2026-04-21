<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokBarangToko extends Model
{
    //
    protected $table = 'stok_barang_toko';

    /**
     * Mass assignable
     */
    protected $fillable = [
        'barang_id',
        'toko_id',
        'stok',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'stok' => 'float',
    ];

    /* =========================
     |  RELATIONSHIPS
     ========================= */

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function toko()
    {
        return $this->belongsTo(IdentitasToko::class, 'toko_id');
    }

    /* =========================
     |  HELPERS
     ========================= */

    public function tambah(float $qty): void
    {
        $this->increment('stok', $qty);
    }

    public function kurang(float $qty): void
    {
        $this->decrement('stok', $qty);
    }
}
