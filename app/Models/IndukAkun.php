<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
        return $this->hasMany(AnakAkun::class, 'id_induk_akun')
            ->orderBy('kode_anak_akun');
    }

    /** Semua anak (untuk count, tanpa ordering overhead) */
    public function allAnakAkuns(): HasMany
    {
        return $this->hasMany(AnakAkun::class, 'id_induk_akun');
    }

    /**
     * Sub Anak Akun melalui Anak Akun (hasManyThrough)
     * Dipakai oleh SubAnakAkunRelationManager di IndukAkun view page.
     */
    public function subAnakAkuns(): HasManyThrough
    {
        return $this->hasManyThrough(
            SubAnakAkun::class,   // target model
            AnakAkun::class,      // intermediate model
            'id_induk_akun',      // FK di anak_akuns → induk_akuns
            'id_anak_akun',       // FK di sub_anak_akuns → anak_akuns
            'id',                 // PK di induk_akuns
            'id'                  // PK di anak_akuns
        );
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function isLeaf(): bool
    {
        // Contoh logika: mengembalikan true jika tidak punya anak akun
        return $this->anakAkuns()->count() === 0;
    }

    public function subAnakAkun()
    {
        return $this->hasManyThrough(SubAnakAkun::class, AnakAkun::class);
    }
}
