<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penjualan extends Model
{
    //
    protected $fillable = [
        'no_nota',
        'tanggal',
        'nama_customer',
        'alamat',
        'metode_pembayaran',
        'bank',
        'no_rekening',
        'kendaraan',
        'nama_sopir',
        'total',
        'bayar',
        'kembalian',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'total' => 'decimal:2',
        'bayar' => 'decimal:2',
        'kembalian' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function details(): HasMany
    {
        return $this->hasMany(PenjualanDetail::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
