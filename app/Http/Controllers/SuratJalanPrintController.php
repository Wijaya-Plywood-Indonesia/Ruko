<?php

namespace App\Http\Controllers;

use App\Models\SuratJalan;
use Illuminate\Http\Request;

class SuratJalanPrintController extends Controller
{
    //
    public function cetak($id)
    {
        $suratJalan = SuratJalan::with([
            'details.barang', // 👈 penting
            'tokoAsal',
            'tokoTujuan',
            'createdBy',
        ])->findOrFail($id);

        return view('SuratJalan.cetakSuratJalanStock', compact('suratJalan'));
    }
}
