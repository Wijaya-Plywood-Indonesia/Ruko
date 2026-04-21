<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokLog extends Model
{
    //
    protected $table = 'stok_logs_toko';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'barang_id',
        'toko_id',
        'tipe',
        'qty',
        'stok_sebelum',
        'stok_sesudah',
        'referensi_type',
        'referensi_id',
        'created_by',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'qty' => 'float',
        'stok_sebelum' => 'float',
        'stok_sesudah' => 'float',
    ];

    /* =========================
     |  RELATIONSHIPS
     ========================= */

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function toko()
    {
        return $this->belongsTo(IdentitasToko::class, 'toko_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Polymorphic-like reference
     * (manual, karena kamu pakai referensi_type + referensi_id)
     */
    public function referensi()
    {
        return match ($this->referensi_type) {
            'surat_jalan' => SuratJalan::find($this->referensi_id),
            // 'penjualan' => Penjualan::find($this->referensi_id),
            // 'pembelian' => Pembelian::find($this->referensi_id),
            default => null,
        };
    }

    /* =========================
     |  SCOPES
     ========================= */

    public function scopeMasuk($query)
    {
        return $query->whereIn('tipe', [
            'pembelian',
            'mutasi_masuk',
            'retur',
        ]);
    }

    public function scopeKeluar($query)
    {
        return $query->whereIn('tipe', [
            'penjualan',
            'mutasi_keluar',
        ]);
    }

    /* =========================
     |  HELPERS
     ========================= */

    public function isMasuk(): bool
    {
        return in_array($this->tipe, [
            'pembelian',
            'mutasi_masuk',
            'retur',
        ]);
    }

    public function isKeluar(): bool
    {
        return in_array($this->tipe, [
            'penjualan',
            'mutasi_keluar',
        ]);
    }

    public function perubahanStok(): int
    {
        return $this->stok_sesudah - $this->stok_sebelum;
    }
}
