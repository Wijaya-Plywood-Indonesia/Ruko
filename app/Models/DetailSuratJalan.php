<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailSuratJalan extends Model
{
    //
    protected $table = 'detail_surat_jalan';

    /**
     * Mass assignable
     */
    protected $fillable = [
        'surat_jalan_id',
        'barang_id',
        'qty_kirim',
        'qty_diterima',
        'catatan',
    ];

    /* =========================
     |  RELATIONSHIPS
     ========================= */

    // Header surat jalan
    public function suratJalan()
    {
        return $this->belongsTo(SuratJalan::class, 'surat_jalan_id');
    }

    // Barang yang dikirim
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    /* =========================
     |  HELPERS
     ========================= */

    public function isDiterima(): bool
    {
        return !is_null($this->qty_diterima);
    }

    public function selisih(): int
    {
        return ($this->qty_kirim ?? 0) - ($this->qty_diterima ?? 0);
    }
}
