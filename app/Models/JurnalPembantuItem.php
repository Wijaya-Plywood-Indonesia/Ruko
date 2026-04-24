<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalPembantuItem extends Model
{
    protected $table = 'jurnal_pembantu_items';

    protected $fillable = [
        'jurnal_pembantu_header_id',
        'urut',
        'jenis_pihak',
        'nama_pihak',
        'nama_barang',
        'no_dokumen',
        'no_referensi',
        'keterangan',
        'banyak',
        'harga',
        'jumlah',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'banyak'  => 'decimal:4',
        'harga'   => 'decimal:4',
        'jumlah'  => 'decimal:4',
        'status'  => 'boolean',
    ];

    // ── Konstanta ─────────────────────────────────────────────────────────────

    const JENIS_PIHAK = [
        'pelanggan' => 'Pelanggan',
        'pemasok'   => 'Pemasok',
        'karyawan'  => 'Karyawan',
        'lain'      => 'Lain-lain',
    ];

    // ── Relasi ────────────────────────────────────────────────────────────────

    public function header(): BelongsTo
    {
        return $this->belongsTo(JurnalPembantuHeader::class, 'jurnal_pembantu_header_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Observer: jumlah = banyak × harga, update total header ───────────────

    protected static function booted(): void
    {
        // Hitung jumlah sebelum simpan
        static::saving(function (self $item) {
            $item->jumlah = (float) ($item->banyak ?? 0) * (float) ($item->harga ?? 0);
        });

        // Update total_nilai di header setelah item berubah
        static::saved(function (self $item) {
            $item->header?->recalculateTotalNilai();
        });

        static::deleted(function (self $item) {
            $item->header?->recalculateTotalNilai();
        });
    }
}
