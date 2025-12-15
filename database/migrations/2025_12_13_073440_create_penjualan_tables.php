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
        /*
        |--------------------------------------------------------------------------
        | Tabel Penjualan (Header / Nota)
        |--------------------------------------------------------------------------
        */
        Schema::create('penjualans', function (Blueprint $table) {
            $table->id();

            // Nota
            $table->string('no_nota')->unique();
            $table->dateTime('tanggal');

            // Customer (SNAPSHOT)
            $table->string('nama_customer');
            $table->text('alamat')->nullable();

            // Pembayaran
            $table->enum('metode_pembayaran', ['TUNAI', 'TRANSFER']);
            $table->string('bank')->nullable();
            $table->string('no_rekening')->nullable();

            // Pengiriman / Surat Jalan
            $table->string('kendaraan')->nullable();
            $table->string('nama_sopir')->nullable();

            // Keuangan
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('bayar', 15, 2)->default(0);
            $table->decimal('kembalian', 15, 2)->default(0);

            // Audit
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Tabel Penjualan Detail (Item Barang)
        |--------------------------------------------------------------------------
        */
        Schema::create('penjualan_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('penjualan_id')
                ->constrained('penjualans')
                ->cascadeOnDelete();

            // Relasi opsional ke master barang
            $table->foreignId('barang_id')
                ->nullable()
                ->constrained('barangs')
                ->nullOnDelete();

            // Snapshot barang
            $table->string('nama_barang');
            $table->string('satuan')->nullable();

            // Qty & Harga
            $table->integer('qty');
            $table->decimal('harga_awal', 15, 2);
            $table->decimal('harga_jual', 15, 2);
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
        Schema::dropIfExists('penjualan_details');
        Schema::dropIfExists('penjualans');
    }
};
