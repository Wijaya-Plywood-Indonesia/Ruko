<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SatuanKonversi extends Model
{
    protected $fillable = [
        'id_satuan_asal',
        'id_satuan_tujuan',
        'nilai_konversi',
        'keterangan',
        'id_barang',
        'berlaku_mulai',
        'berlaku_sampai',
    ];

    protected $casts = [
        'nilai_konversi' => 'integer',
        'berlaku_mulai' => 'date',
        'berlaku_sampai' => 'date',
    ];

    public function satuanAsal()
    {
        return $this->belongsTo(Satuan::class, 'id_satuan_asal');
    }

    public function satuanTujuan()
    {
        return $this->belongsTo(Satuan::class, 'id_satuan_tujuan');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    /* ─── LOCAL SCOPES (Logika Forensik) ─────────────────────────────── */

    /**
     * Scope untuk mengambil konversi yang aktif pada tanggal tertentu.
     * Digunakan untuk audit/forensik transaksi lama.
     */
    public function scopeAktif(Builder $query, $tanggal = null)
    {
        $tanggal = $tanggal ?? now();

        return $query->where('berlaku_mulai', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('berlaku_sampai')
                    ->orWhere('berlaku_sampai', '>=', $tanggal);
            });
    }

    public function scopeUntukBarang(Builder $query, $barangId = null)
    {
        return $query->where(function ($q) use ($barangId) {
            $q->whereNull('barang_id')
                ->orWhere('barang_id', $barangId);
        });
    }
}
