<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratJalan extends Model
{
    //
    protected $table = 'surat_jalan';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'no_surat_jalan',
        'tanggal_kirim',
        'toko_asal_id',
        'toko_tujuan_id',
        'status',
        'keterangan',
        'nama_supir',
        'jeniskendaraan',
        'plat',
        'created_by',
        'validated_by',
    ];

    /**
     * Default attribute values
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'tanggal_kirim' => 'date',
    ];

    /* =========================
     |  RELATIONSHIPS
     ========================= */

    // Toko asal (gudang pusat)
    public function tokoAsal()
    {
        return $this->belongsTo(IdentitasToko::class, 'toko_asal_id');
    }

    // Toko tujuan (toko ecer)
    public function tokoTujuan()
    {
        return $this->belongsTo(IdentitasToko::class, 'toko_tujuan_id');
    }

    // Pembuat surat jalan
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Validator / penerima
    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Detail barang (akan kita buat setelah ini)
    public function details()
    {
        return $this->hasMany(DetailSuratJalan::class, 'surat_jalan_id');
    }

    /* =========================
     |  SCOPES
     ========================= */

    public function scopeDikirim($query)
    {
        return $query->where('status', 'dikirim');
    }

    public function scopeDiterima($query)
    {
        return $query->where('status', 'diterima');
    }

    /* =========================
     |  HELPERS
     ========================= */

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isDikirim(): bool
    {
        return $this->status === 'dikirim';
    }

    public function isDiterima(): bool
    {
        return $this->status === 'diterima';
    }

    public function isDitolak(): bool
    {
        return $this->status === 'ditolak';
    }
}
