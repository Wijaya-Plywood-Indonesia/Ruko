<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProduksiTelur extends Model
{
    protected $fillable = [
        'tanggal',
        'jumlah_telur_butir',
        'jumlah_telur_kilo',
        'jumlah_telur_tray',
        'is_validated',
        'validated_by',
        'validated_at',
        'created_by',
    ];

    protected $casts = [
        'tanggal'      => 'date',
        'validated_at' => 'datetime',
        'is_validated' => 'boolean',
    ];

    // ─── Relations ───────────────────────────────────────────

    public function details()
    {
        return $this->hasMany(DetailProduksiTelur::class, 'id_produksi_telur');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Business Logic ──────────────────────────────────────

    /**
     * Recalculate & persist header totals from details.
     * Dipanggil setiap kali detail berubah.
     */
    public function recalculateTotals(): void
    {
        $this->jumlah_telur_butir = $this->details()->sum('jumlah_telur_butir');
        $this->jumlah_telur_kilo  = $this->details()->sum('jumlah_telur_kilo');
        $this->jumlah_telur_tray  = $this->details()->sum('jumlah_telur_tray');
        $this->saveQuietly(); // tidak trigger event loop
    }

    /**
     * Validasi produksi hari ini.
     */
    public function validate(): void
    {
        $this->update([
            'is_validated' => true,
            'validated_by' => Auth::id(),
            'validated_at' => now(),
        ]);
    }

    /**
     * Apakah data boleh diedit oleh user saat ini?
     */
    public function isEditable(): bool
    {
        if (! $this->is_validated) return true;

        return Auth::user()->hasRole('super_admin');
    }
}
