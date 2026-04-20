<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnPenjualan extends Model
{
    protected $table = "penjualan_return";
    protected $guarded = ["id"];

    protected $casts = [
        'tanggal' => 'datetime',
        'total' => 'decimal:2',
        'bayar' => 'decimal:2',
        'kembalian' => 'decimal:2',
        'is_member' => 'boolean',
        'user_id' => 'integer',
        'validated_by' => 'integer',
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
    public function details()
    {
        return $this->hasMany(DetailPenjualan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
    public function rekeningPerusahaan()
    {
        return $this->belongsTo(
            RekeningPerusahaan::class,
            'no_rekening',   // kolom di penjualans
            'no_rekening'    // kolom di rekening_perusahaan
        );
    }
    public function toko()
    {
        return $this->belongsTo(IdentitasToko::class, 'toko_id');
    }
    // Di dalam class ReturnPenjualan
    public function details_return()
    {
        return $this->hasMany(ReturnPenjualanDetail::class, 'id_return', 'id');
    }
}
