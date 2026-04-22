<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnPenjualanDetail extends Model
{
    protected $table = "penjualan_return_detail";
    protected $guarded = ["id"];

    protected $casts = [
        'harga_awal' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'potongan' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'qty' => 'decimal:2',
    ];

    public function returnPenjualan()
    {
        return $this->belongsTo(ReturnPenjualan::class, 'id_return');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}