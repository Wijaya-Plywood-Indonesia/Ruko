<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AkunGroup extends Model
{
    use HasFactory;

    protected $table = 'akun_groups';

    protected $fillable = [
        'nama',
        'parent_id',
        'order',
        'hidden',
        'tipe',
    ];

    protected $casts = [
        'hidden' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Many-to-many: AkunGroup <-> AnakAkun (pivot lama — jangan dihapus)
     */
    public function anakAkuns()
    {
        return $this->belongsToMany(
            AnakAkun::class,
            'akun_group_anak_akun',
            'akun_group_id',
            'anak_akun_id'
        )->withTimestamps();
    }

    /**
     * Many-to-many: AkunGroup <-> SubAnakAkun (pivot baru untuk neraca telur)
     */
    public function subAnakAkuns()
{
    return $this->belongsToMany(
        SubAnakAkun::class,
        'akun_group_sub_anak_akun',
        'akun_group_id',
        'sub_anak_akun_id'
    )
    ->withPivot('id')
    ->withTimestamps();
}

    /**
     * Parent Group
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Children Group
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('order');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isLeaf(): bool
    {
        return !$this->children()->exists();
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeLeaf($query)
    {
        return $query->doesntHave('children');
    }

    public function scopeVisible($query)
    {
        return $query->where('hidden', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function getTotalAnakAkunsAttribute(): int
    {
        if ($this->children()->count() === 0) {
            return $this->anakAkuns()->count();
        }

        return $this->children()
            ->withCount('anakAkuns')
            ->get()
            ->sum(fn($child) => $child->anak_akuns_count);
    }
}