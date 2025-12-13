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
       | Tabel Kategori
       |--------------------------------------------------------------------------
       */
        Schema::create('kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('kategoris')
                ->nullOnDelete();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Tabel Satuan
        |--------------------------------------------------------------------------
        */
        Schema::create('satuans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_satuan'); // pcs, botol, dus
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Tabel Barang (Master)
        |--------------------------------------------------------------------------
        */
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();

            $table->string('kode_barang')->unique();
            $table->string('barcode')->nullable()->unique();

            $table->string('nama_barang');

            $table->foreignId('id_kategori')
                ->constrained('kategoris')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_satuan')
                ->constrained('satuans')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);

            $table->integer('stok_minimum')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('satuans');
        Schema::dropIfExists('kategoris');
        Schema::dropIfExists('barangs');
    }
};
