<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stok_logs_toko', function (Blueprint $table) {
            $table->string('tipe')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stok_logs_toko', function (Blueprint $table) {
            $table->enum('tipe', [
                'pembelian',
                'penjualan',
                'mutasi_masuk',
                'mutasi_keluar',
                'adjustment',
                'retur'
            ])->change();
        });
    }
};