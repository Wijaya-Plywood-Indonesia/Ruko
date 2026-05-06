<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'nama',
        'telepon',
        'alamat',
        'email',
        'npwp',
        'is_active',
        'keterangan_tambahan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pembelians(): HasMany
    {
        return $this->hasMany(Pembelian::class);
    }
}