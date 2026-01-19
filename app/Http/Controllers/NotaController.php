<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\RekeningPerusahaan;

class NotaController extends Controller
{
    //
    public function print(Penjualan $penjualan)
    {
        $penjualan->load([
            'details.barang',
            'user',
            'rekeningPerusahaan', // 👈 load relasi
        ]);

        $totalPotonganNota = $penjualan->details->sum(function ($detail) {
            return ($detail->potongan ?? 0) * $detail->qty;
        });

        return view('penjualans.cetakNota', compact(
            'penjualan',
            'totalPotonganNota'
        ));
    }
}
