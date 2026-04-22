<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndukAkun extends Model
{
    protected $fillable = [
        'kode_induk_akun',
        'nama_induk_akun',
        'keterangan',
        'saldo_normal',
        'status',
        'created_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function anakAkuns(): HasMany
    {
        return $this->hasMany(AnakAkun::class, 'id_induk_akun');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope
    |--------------------------------------------------------------------------
    */

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }
    /** Semua anak (untuk count) */
    public function allAnakAkuns()
    {
        return $this->hasMany(AnakAkun::class, 'id_induk_akun');
    }
}