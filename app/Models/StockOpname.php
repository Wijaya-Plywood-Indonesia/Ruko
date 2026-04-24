<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class StockOpname extends Model
{
    //
    protected $table = 'stock_opnames';

    protected $fillable = [
        'no_opname',
        'toko_id',
        'tanggal_opname',
        'status',
        'catatan',
        'catatan_approval',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'tanggal_opname' => 'date',
        'approved_at' => 'datetime',
    ];

    /* =========================
     |  AUTO GENERATE NOMOR
     ========================= */

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (StockOpname $model) {
            if (empty($model->no_opname)) {
                $now = Carbon::now();
                $datePart = $now->format('Ymd');
                $timePart = $now->format('His') . $now->format('u');
                $model->no_opname = 'SO-' . $datePart . '-' . $timePart;
            }
        });
    }

    /* =========================
     |  RELATIONSHIPS
     ========================= */

    public function toko()
    {
        return $this->belongsTo(IdentitasToko::class, 'toko_id');
    }

    public function details()
    {
        return $this->hasMany(StockOpnameDetail::class, 'stock_opname_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /* =========================
     |  HELPERS
     ========================= */

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
    public function isMenunggu(): bool
    {
        return $this->status === 'menunggu';
    }
    public function isDisetujui(): bool
    {
        return $this->status === 'disetujui';
    }
    public function isDitolak(): bool
    {
        return $this->status === 'ditolak';
    }
}
