<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    //
    protected $table = 'kategoris';

    protected $fillable = [
        'nama_kategori',
        'parent_id',
    ];

    // Parent kategori
    public function parent()
    {
        return $this->belongsTo(Kategori::class, 'parent_id');
    }

    // Sub kategori
    public function children()
    {
        return $this->hasMany(Kategori::class, 'parent_id');
    }

    // Barang dalam kategori
    public function barangs()
    {
        return $this->hasMany(Barang::class, 'id_kategori');
    }
}
