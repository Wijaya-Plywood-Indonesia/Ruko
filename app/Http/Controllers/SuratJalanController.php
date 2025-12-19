<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Illuminate\Http\Request;

class SuratJalanController extends Controller
{
    //

    public function print(Penjualan $penjualan)
    {
        $penjualan->load([
            'details.barang',
        ]);

        return view('penjualans.cetakSuratJalan', compact('penjualan'));

    }

}
