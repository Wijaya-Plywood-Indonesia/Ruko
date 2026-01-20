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
        Schema::create('stok_barang_toko', function (Blueprint $table) {
            $table->id();

            $table->foreignId('barang_id')
                ->constrained('barangs')
                ->restrictOnDelete();

            $table->foreignId('toko_id')
                ->constrained('identitas_toko')
                ->restrictOnDelete();

            $table->integer('stok')->default(0);
            $table->unique(['barang_id', 'toko_id']);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_barang_toko');
    }
};
