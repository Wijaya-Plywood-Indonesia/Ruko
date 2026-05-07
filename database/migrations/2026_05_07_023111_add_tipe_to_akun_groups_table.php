<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akun_groups', function (Blueprint $table) {
            $table->enum('tipe', [
                'pendapatan',       // Penjualan/Pendapatan → POSITIF
                'hpp',              // Harga Pokok Penjualan → PENGURANG
                'beban_produksi',   // Biaya Produksi (bagian dari HPP) → PENGURANG
                'beban_usaha',      // Beban Operasional → PENGURANG
                'pendapatan_lain',  // Pendapatan lain-lain → POSITIF
                'beban_lain',       // Beban lain-lain → PENGURANG
                'lainnya',          // Tidak masuk kalkulasi rumus
            ])->nullable()->after('hidden');
        });
    }

    public function down(): void
    {
        Schema::table('akun_groups', function (Blueprint $table) {
            $table->dropColumn('tipe');
        });
    }
};