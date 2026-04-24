<?php

namespace App\Models;

use App\Services\NomorSuratJalanService;
use Illuminate\Database\Eloquent\Model;

class SuratJalan extends Model
{
    protected $table = 'surat_jalan';

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

    protected $attributes = [
        'status' => 'draft',
    ];

    protected $casts = [
        'tanggal_kirim' => 'date',
    ];

    /* =========================
     |  AUTO GENERATE NOMOR
     ========================= */

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SuratJalan $model) {
            if (empty($model->no_surat_jalan)) {
                $model->no_surat_jalan = NomorSuratJalanService::generate(
                    $model->tanggal_kirim
                    ? $model->tanggal_kirim->toDateString()
                    : null
                );
            }
        });
    }

    /* =========================
     |  RELATIONSHIPS
     ========================= */

    public function tokoAsal()
    {
        return $this->belongsTo(IdentitasToko::class, 'toko_asal_id');
    }

    public function tokoTujuan()
    {
        return $this->belongsTo(IdentitasToko::class, 'toko_tujuan_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

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