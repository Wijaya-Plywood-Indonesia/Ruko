<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Barang extends Model
{
    //
    protected $table = 'barangs';

    protected $fillable = [
        'kode_barang',
        'barcode',
        'nama_barang',
        'id_sub_anak_akun',
        'id_kategori',
        'id_satuan',
        'harga_beli',
        'harga_jual',
        'stok_minimum',
        'is_active',
        'akun_pendapatan_id',
        'akun_hpp_id',
    ];

    protected $casts = [
        'harga_beli' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relasi
    |--------------------------------------------------------------------------
    */

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'id_satuan');
    }
    public function stok_toko()
    {
        return $this->hasOne(StokBarangToko::class, 'barang_id');
    }
    public function penjualanDetails()
    {
        return $this->hasMany(DetailPenjualan::class, 'barang_id');
    }

    public function stokBarangTokos()
    {
        // Pastikan foreign key 'barang_id' sesuai dengan di database
        return $this->hasMany(StokBarangToko::class, 'barang_id');
    }

    public function komposisi()
    {
        return $this->hasMany(Komposisi::class, 'id_barang');
    }

    // Barang ini dipakai sebagai bahan dalam detail komposisi
    public function detailKomposisi()
    {
        return $this->hasMany(DetailKomposisi::class, 'id_barang');
    }

    // Barang ini dipakai sebagai bahan aktual dalam produksi
    public function produksiPakanCampuran()
    {
        return $this->hasMany(ProduksiPakanCampuran::class);
    }

    public function produksiPakanMentah()
    {
        return $this->hasMany(ProduksiPakanMentah::class);
    }

    public function subAnakAkun()
    {
        // Parameter kedua adalah nama kolom foreign key yang kita buat di migration tadi
        return $this->belongsTo(SubAnakAkun::class, 'id_sub_anak_akun');
    }

    /** Relasi BARU: Akun Pendapatan */
    public function akunPendapatan()
    {
        return $this->belongsTo(SubAnakAkun::class, 'akun_pendapatan_id');
    }

    /** Relasi BARU: Akun HPP */
    public function akunHpp()
    {
        return $this->belongsTo(SubAnakAkun::class, 'akun_hpp_id');
    }

    public function getStokBukuBesarAttribute()
    {
        $subAkun = $this->subAnakAkun;
        $kodeAkun = $subAkun?->kode_sub_anak_akun;

        if (!$kodeAkun) {
            return 0.0;
        }

        $transaksis = JurnalUmum::where('no_akun', $kodeAkun)
            ->select('map', DB::raw('SUM(COALESCE(banyak, 0)) as total_banyak'))
            ->groupBy('map')
            ->get();

        $totalQty = 0.0;
        foreach ($transaksis as $trx) {
            $isDebit = in_array(strtolower($trx->map), ['d', 'debit']);
            $qty = (float) $trx->total_banyak;
            if ($isDebit) {
                $totalQty += $qty;
            } else {
                $totalQty -= $qty;
            }
        }

        return $totalQty;
    }
}
