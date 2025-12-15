<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPenjualan extends Model
{
    //
    protected $fillable = [
        'penjualan_id',
        'barang_id',
        'nama_barang',
        'satuan',
        'qty',
        'harga_awal',
        'harga_jual',
        'subtotal',
        'keterangan',
    ];

    protected $casts = [
        'qty' => 'integer',
        'harga_awal' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
