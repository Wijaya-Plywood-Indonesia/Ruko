<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembelianMetodePembayaran extends Model
{
    // TODO: Ganti ke enum saat release
    const METODE_TUNAI = 'tunai';
    const METODE_TRANSFER = 'transfer';
    const METODE_CICILAN = 'cicilan';
    const METODE_LAINNYA = 'Down Payment (DP)';

    protected $fillable = [
        'pembelian_id',
        'created_by',
        'validated_by',
        'tanggal_validasi',
        'tanggal_bayar',
        'amount',
        'payment_method',
        'reference_number',
        'catatan',
    ];

    protected $casts = [
        'tanggal_bayar' => 'date',
        'tanggal_validasi' => 'datetime',
        'amount' => 'decimal:2',
    ];

    // ==================
    // Relationships
    // ==================

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // ==================
    // Helper
    // ==================

    public static function labelMetode(): array
    {
        return [
            self::METODE_TUNAI => 'Tunai',
            self::METODE_TRANSFER => 'Transfer Bank',
            self::METODE_CICILAN => 'Cicilan',
            self::METODE_LAINNYA => 'Down Payment (DP)',
        ];
    }

    // public function getLabelMetodeAttribute(): string
    // {
    //     $labels = self::labelMetode();
    //     $key = str_replace(['💵 ', '🏦 ', '📱 ', '💳 ', '📅 ', '📝 '], '', $labels[$this->payment_method] ?? $this->payment_method);
    //     return $key;
    // }
}
