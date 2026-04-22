<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubAnakAkun extends Model
{
    protected $fillable = [
        'id_anak_akun',
        'kode_sub_anak_akun',
        'nama_sub_anak_akun',
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

    public function anakAkun(): BelongsTo
    {
        return $this->belongsTo(AnakAkun::class, 'id_anak_akun');
    }

    public function akunGroups()
    {
        return $this->belongsToMany(
            AkunGroup::class,
            'akun_group_sub_anak_akun',  // nama tabel pivot
            'sub_anak_akun_id',          // FK ke SubAnakAkun di pivot
            'akun_group_id'              // FK ke AkunGroup di pivot
        )->withTimestamps();
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
    public function mappingAkunProduksi(): HasMany
    {
        return $this->hasMany(MappingAkunProduksi::class, 'kode_akun', 'kode_sub_anak_akun');
    }
}
