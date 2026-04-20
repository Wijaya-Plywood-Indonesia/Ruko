<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penjualan_return', function (Blueprint $table) {
            // Tambahkan kolom yang Anda inginkan di sini

            // $table->foreignId('toko_id')
            //     ->nullable()
            //     ->constrained('identitas_toko')
            //     ->restrictOnDelete();


        });
    }

    public function down(): void
    {
        Schema::table('penjualan_return', function (Blueprint $table) {
            // $table->dropColumn(['toko_id']);
        });
    }
};
