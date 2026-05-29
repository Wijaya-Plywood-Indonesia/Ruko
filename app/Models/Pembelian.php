<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembelian extends Model
{
    // TODO: Ganti ke enum saat release
    const STATUS_DRAFT = 'draft';
    const STATUS_HUTANG = 'hutang';
    const STATUS_CICILAN = 'cicilan';
    const STATUS_LUNAS = 'lunas';
    const STATUS_BATAL = 'batal';

    protected $fillable = [
        'created_by',
        'validated_by',
        'tanggal_validasi',
        'nomor_nota',
        'tanggal',
        'foto',
        'supplier_id',
        'supplier_name',
        'supplier_phone',
        'supplier_address',
        'supplier_npwp',
        'sub_total',
        'total_diskon',
        'total_ppn',
        'ongkir',
        'biaya_lain',
        'grand_total',
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_validasi' => 'datetime',
        'foto' => 'array',
        'sub_total' => 'decimal:2',
        'total_diskon' => 'decimal:2',
        'total_ppn' => 'decimal:2',
        'ongkir' => 'decimal:2',
        'biaya_lain' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    // ==================
    // Status Labels
    // ==================

    public static function labelStatus(): array
    {
        return [
            self::STATUS_LUNAS => 'Lunas',
            self::STATUS_DRAFT => 'Belum Diproses',
            self::STATUS_HUTANG => 'Belum Lunas (Hutang)',
            self::STATUS_CICILAN => 'Dibayar Sebagian (Cicilan)',
            self::STATUS_BATAL => 'Dibatalkan',
        ];
    }

    public static function warnaBadgeStatus(): array
    {
        return [
            self::STATUS_DRAFT => 'gray',
            self::STATUS_HUTANG => 'danger',
            self::STATUS_CICILAN => 'warning',
            self::STATUS_LUNAS => 'success',
            self::STATUS_BATAL => 'gray',
        ];
    }

    // ==================
    // Relationships
    // ==================

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function detailPembelians(): HasMany
    {
        return $this->hasMany(DetailPembelian::class);
    }

    public function metodePembayarans(): HasMany
    {
        return $this->hasMany(PembelianMetodePembayaran::class, "pembelian_id");
    }

    // ==================
    // Helper Methods
    // ==================

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
    public function isLunas(): bool
    {
        return $this->status === self::STATUS_LUNAS;
    }
    public function isHutang(): bool
    {
        return $this->status === self::STATUS_HUTANG;
    }
    public function isCicilan(): bool
    {
        return $this->status === self::STATUS_CICILAN;
    }
    public function isBatal(): bool
    {
        return $this->status === self::STATUS_BATAL;
    }

    public function totalSudahDibayar(): float
    {
        return (float) $this->metodePembayarans()->sum('amount');
    }

    public function sisaTagihan(): float
    {
        return (float) $this->grand_total - $this->totalSudahDibayar();
    }

    public function hitungGrandTotal(
        ?float $subTotal    = null,
        ?float $totalDiskon = null,
        ?float $totalPpn    = null,
        ?float $ongkir      = null,
        ?float $biayaLain   = null,
    ): float {
        return max(
            0.0,
            ($subTotal    ?? (float) $this->sub_total)
                - ($totalDiskon ?? (float) $this->total_diskon)
                + ($totalPpn    ?? (float) $this->total_ppn)
                + ($ongkir      ?? (float) $this->ongkir)
                + ($biayaLain   ?? (float) $this->biaya_lain)
        );
    }
}
