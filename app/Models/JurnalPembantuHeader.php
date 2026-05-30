<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JurnalPembantuHeader extends Model
{
    protected $table = 'jurnal_pembantu_headers';

    protected $fillable = [
        'no_jurnal_pembantu',
        'tgl_transaksi',
        'jenis_transaksi',
        'modul_asal',
        'jurnal',
        'no_akun',
        'nama_akun',
        'map',
        'keterangan',
        'no_dokumen',
        'catatan_internal',
        'total_nilai',
        'status',
        'adalah_jurnal_balik',
        'membalik_id',
        'dibuat_oleh',
        'diubah_oleh',
        'diposting_oleh',
        'tgl_posting',
    ];

    protected $casts = [
        'tgl_transaksi'       => 'date',
        'tgl_posting'         => 'datetime',
        'total_nilai'         => 'decimal:4',
        'adalah_jurnal_balik' => 'boolean',
    ];

    // ── Konstanta Status ──────────────────────────────────────────────────────

    const STATUS_DRAFT      = 'draft';
    const STATUS_DIPOSTING  = 'diposting';
    const STATUS_DIBALIK    = 'dibalik';
    const STATUS_DIBATALKAN = 'dibatalkan';

    const STATUSES = [
        self::STATUS_DRAFT      => 'Draft',
        self::STATUS_DIPOSTING  => 'Diposting',
        self::STATUS_DIBALIK    => 'Dibalik',
        self::STATUS_DIBATALKAN => 'Dibatalkan',
    ];

    // ── Konstanta Jenis Transaksi ─────────────────────────────────────────────

    const JENIS = [
        'bm'    => 'Bukti Masuk / Pembelian',
        'bk'    => 'Bukti Keluar / Penjualan',
        'dp'    => 'Down Payment / Pelunasan',
        'pk'    => 'Produksi Pakan / Kandang',
        'gaji'  => 'Penggajian',
        'balik' => 'Jurnal Balik',
        'lain'  => 'Lain-lain',
    ];

    // ── Konstanta Modul Asal (penjualan + produksi telur) ────────────────────

    const MODUL_ASAL = [
        'penjualan_telur'  => 'Penjualan Telur',
        'retur_penjualan'  => 'Retur Penjualan',
        'pembelian_pakan'  => 'Pembelian Pakan',
        'pembelian_doc'    => 'Pembelian DOC (Bibit)',
        'produksi_telur'   => 'Produksi Telur (Kandang)',
        'penggajian'       => 'Penggajian',
        'lain'             => 'Lain-lain',
    ];

    // ── Konstanta MAP ─────────────────────────────────────────────────────────

    const MAP = [
        'd' => 'Debet',
        'k' => 'Kredit',
    ];

    // ── Relasi ────────────────────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(JurnalPembantuItem::class, 'jurnal_pembantu_header_id')
            ->orderBy('urut');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }

    public function dipostingOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diposting_oleh');
    }

    public function jurnalYangDibalik(): BelongsTo
    {
        return $this->belongsTo(self::class, 'membalik_id');
    }

    public function jurnalBalik(): HasOne
    {
        return $this->hasOne(self::class, 'membalik_id');
    }

    // ── Helper: semua header dalam satu nomor jurnal ──────────────────────────

    public function seJurnal()
    {
        return static::where('jurnal', $this->jurnal)->get();
    }

    // ── Helper: validasi balance D = K ────────────────────────────────────────

    public function isBalanced(): bool
    {
        $rows        = static::where('jurnal', $this->jurnal)->get();
        $totalDebet  = $rows->where('map', 'd')->sum('total_nilai');
        $totalKredit = $rows->where('map', 'k')->sum('total_nilai');

        return round((float) $totalDebet, 4) === round((float) $totalKredit, 4);
    }

    // ── Helper: cek status ────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_DIPOSTING;
    }

    // ── Hitung ulang total_nilai dari items aktif ─────────────────────────────
    // jumlah per item = banyak × harga (sudah dikalkulasi di model JurnalPembantuItem)

    public function recalculateTotalNilai(): void
    {
        $total = $this->items()
            ->where('status', true)
            ->sum('jumlah');

        $this->update(['total_nilai' => $total]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_DIPOSTING);
    }
}
