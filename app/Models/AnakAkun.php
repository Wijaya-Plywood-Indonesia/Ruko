<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnakAkun extends Model
{
    protected $table = 'anak_akuns';

    protected $fillable = [
        'id_induk_akun',
        'kode_anak_akun',
        'nama_anak_akun',
        'keterangan',
        'parent',
        'status',
        'saldo_normal',
        'created_by',
    ];



    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    // Anak akun yang menjadi sub-parent (children rekursif)
    public function children()
    {
        return $this->hasMany(AnakAkun::class, 'parent')
            ->orderBy('kode_anak_akun');
    }
    public function indukAkun(): BelongsTo
    {
        return $this->belongsTo(IndukAkun::class, 'id_induk_akun');
    }

    public function subAnakAkuns(): HasMany
    {
        return $this->hasMany(SubAnakAkun::class, 'id_anak_akun');
    }
    /**
     * Parent Self Reference
     */
    public function parentAkun()
    {
        return $this->belongsTo(self::class, 'parent');
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

    /**
     * Many to Many - Akun Group
     */
    public function akunGroups()
    {
        return $this->belongsToMany(
            AkunGroup::class,
            'akun_group_anak_akun'
        )->withTimestamps();
    }
    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if akun is leaf (tidak punya children)
     */
    public function isLeaf(): bool
    {
        return !$this->children()->exists();
    }

    /**
     * Check if akun punya children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }
    public function getLabelAttribute(): string
    {
        return "{$this->kode_anak_akun} - {$this->nama_anak_akun}";
    }
}

