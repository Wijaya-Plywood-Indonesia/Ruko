<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('penjualan_return', function (Blueprint $table) {
            $table->foreignId('toko_id')
                ->after('barang_id') 
                ->constrained('identitas_toko')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualan_return', function (Blueprint $table) {
            // Hapus foreign key dan kolom jika migration di-rollback
            $table->dropForeign(['toko_id']);
            $table->dropColumn('toko_id');
        });
    }
};