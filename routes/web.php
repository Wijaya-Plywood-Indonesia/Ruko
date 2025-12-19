<?php

use App\Http\Controllers\NotaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuratJalanController;

Route::get('/surat-jalan/{penjualan}', [SuratJalanController::class, 'print'])
    ->name('surat-jalan.cetak');

Route::get('/nota/{penjualan}/cetak', [NotaController::class, 'print'])
    ->name('nota.cetak');

Route::get('/', function () {
    return view('welcome');
});

