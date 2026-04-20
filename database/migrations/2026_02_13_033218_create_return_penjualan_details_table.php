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
        
        /*
        |--------------------------------------------------------------------------
        | Tabel Penjualan Detail (Item Barang)
        |--------------------------------------------------------------------------
        */
        Schema::create('penjualan_return_detail', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_return')
                ->constrained('penjualan_return')
                ->cascadeOnDelete();

            // Relasi opsional ke master barang
            $table->foreignId('id_barang')
                ->nullable()
                ->constrained('barangs')
                ->nullOnDelete();

            // Snapshot barang
            $table->string('nama_barang');
            $table->string('satuan')->nullable();

            // Qty & Harga
            $table->decimal('harga_awal', 15, 2);
            $table->decimal('harga_jual', 15, 2);
            $table->decimal('potongan', 15, 2);
            $table->integer('qty');
            $table->decimal('subtotal', 15, 2);

            // Keterangan (diskon / negosiasi)
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan_return_detail');
    }
};
