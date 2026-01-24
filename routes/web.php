<?php

use App\Http\Controllers\NotaController;
use App\Http\Controllers\NotaThermalController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuratJalanController;
use App\Http\Controllers\SuratJalanPrintController;

Route::get('/surat-jalan/{id}/cetak', [SuratJalanPrintController::class, 'cetak'])
    ->name('surat-jalan.cetak');

Route::get('/surat-jalan/{penjualan}', [SuratJalanController::class, 'print'])
    ->name('surat-jalan.cetak');

Route::get('/nota/{penjualan}/cetak', [NotaController::class, 'print'])
    ->name('nota.cetak');

Route::get('/nota/{penjualan}/cetakThermal', [NotaThermalController::class, 'print'])
    ->name('nota.cetakThermal');

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', function () {
    return redirect('/admin');
});
