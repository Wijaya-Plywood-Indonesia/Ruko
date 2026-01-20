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
        Schema::create('stok_logs_toko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')
                ->constrained('barangs')
                ->restrictOnDelete();

            $table->foreignId('toko_id')
                ->constrained('identitas_toko')
                ->restrictOnDelete();

            $table->enum('tipe', [
                'pembelian',   // stok masuk
                'penjualan',   // stok keluar
                'mutasi_masuk',
                'mutasi_keluar',
                'adjustment',
                'retur'
            ]);
            $table->integer('qty'); // + masuk, - keluar (atau pakai tipe)

            $table->integer('stok_sebelum');
            $table->integer('stok_sesudah');

            $table->string('referensi_type'); // penjualan, pembelian, surat_jalan
            $table->unsignedBigInteger('referensi_id')->nullable();

            $table->foreignId('created_by')
                ->constrained('users');
            $table->index(['barang_id', 'toko_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_logs_toko');
    }
};
