<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Illuminate\Http\Request;

class NotaThermalController extends Controller
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

        return view('penjualans.cetakNotaThermal', compact(
            'penjualan',
            'totalPotonganNota'
        ));
    }
}
