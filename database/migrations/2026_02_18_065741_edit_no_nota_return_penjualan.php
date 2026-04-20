<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
    {
        Schema::table('penjualan_return', function (Blueprint $table) {
            // Menghapus constraint unique. 
            // Nama indeks default Laravel biasanya 'nama_tabel_nama_kolom_unique'
            $table->dropUnique(['no_nota']); 
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_return', function (Blueprint $table) {
            // Mengembalikan menjadi unique jika migrasi di-rollback
            $table->unique('no_nota');
        });
    }
};
