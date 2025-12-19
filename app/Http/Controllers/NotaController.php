<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Illuminate\Http\Request;

class NotaController extends Controller
{
    //
    public function print(Penjualan $penjualan)
    {
        $penjualan->load(['details.barang', 'user']);

        return view('penjualans.cetakNota', compact('penjualan'));

    }
}
