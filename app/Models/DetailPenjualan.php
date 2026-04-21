<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPenjualan extends Model
{
    //
    protected $table = 'penjualan_details';
    protected $fillable = [
        'penjualan_id',
        'barang_id',
        'nama_barang',
        'satuan',
        'qty',
        'harga_awal',
        'harga_jual',
        'potongan',
        'subtotal',
        'keterangan',
    ];

    protected $casts = [
        'qty' => 'float',
        'harga_awal' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function search_barang()
    {
        // return Barang::query()
        //     ->leftJoin($t, function ($q) use ($t, $toko) {
        //         $q->on("$t.barang_id", '=', 'barangs.id')
        //             ->where("$t.toko_id", $toko);
        //     })
        //     ->select(
        //         'barangs.*',
        //         DB::raw("COALESCE($t.stok, 0) as stok_aktual")
        //     )
        //     ->where(function ($query) {
        //         $query->where('barangs.nama_barang', 'like', "%{$this->search}%")
        //             ->orWhere('barangs.barcode', 'like', "%{$this->search}%");
        //     })
        //     ->limit(8)
        //     ->get();
    }
}
