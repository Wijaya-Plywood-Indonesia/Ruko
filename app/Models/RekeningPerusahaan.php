<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RekeningPerusahaan extends Model
{
    protected $table = 'rekening_perusahaan';

    protected $fillable = [
        'pemilik_rekening',
        'nama_bank',
        'no_rekening',
        'atas_nama',
        'sub_anak_akun_id', // ← FK ke sub_anak_akuns
    ];

    protected $casts = [
        'sub_anak_akun_id' => 'integer',
    ];

    // ── Relasi ke SubAnakAkun (akun jurnal) ───────────────────────────────────
    public function subAnakAkun(): BelongsTo
    {
        return $this->belongsTo(SubAnakAkun::class, 'sub_anak_akun_id');
    }

    // ── Helper: ambil kode akun untuk jurnal ──────────────────────────────────
    // Contoh penggunaan: $rekening->kodeAkun() → '1212-00'
    public function kodeAkun(): ?string
    {
        return $this->subAnakAkun?->kode_sub_anak_akun;
    }

    // ── Helper: ambil nama akun untuk jurnal ──────────────────────────────────
    public function namaAkun(): ?string
    {
        return $this->subAnakAkun?->nama_sub_anak_akun;
    }

    // ── Relasi balik ke Penjualan ─────────────────────────────────────────────
    public function penjualans(): HasMany
    {
        return $this->hasMany(Penjualan::class, 'no_rekening', 'no_rekening');
    }
}