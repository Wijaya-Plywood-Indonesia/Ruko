<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Mengubah kolom stok, qty, dan sejenisnya dari integer menjadi decimal(15, 2).
     *
     * Tabel yang terpengaruh:
     *  - barangs              : stok_minimum
     *  - penjualan_details    : qty, potongan
     *  - stok_barang_toko     : stok
     *  - stok_logs_toko       : qty, stok_sebelum, stok_sesudah
     *  - detail_surat_jalan   : qty_kirim, qty_diterima
     *  - penjualan_return_detail : qty
     */
    public function up(): void
    {
        // Nonaktifkan strict mode sementara agar nilai '0000-00-00 00:00:00'
        // pada kolom timestamp lama tidak menyebabkan error saat ALTER TABLE.
        DB::statement("SET SESSION sql_mode = ''");

        /*
        |--------------------------------------------------------------------------
        | barangs — stok_minimum
        |--------------------------------------------------------------------------
        */
        Schema::table('barangs', function (Blueprint $table) {
            $table->decimal('stok_minimum', 15, 2)->default(0)->change();
        });

        /*
        |--------------------------------------------------------------------------
        | penjualan_details — qty, potongan
        |--------------------------------------------------------------------------
        */
        Schema::table('penjualan_details', function (Blueprint $table) {
            $table->decimal('qty', 15, 2)->change();
            $table->decimal('potongan', 15, 2)->nullable()->change();
        });

        /*
        |--------------------------------------------------------------------------
        | stok_barang_toko — stok
        |--------------------------------------------------------------------------
        */
        Schema::table('stok_barang_toko', function (Blueprint $table) {
            $table->decimal('stok', 15, 2)->default(0)->change();
        });

        /*
        |--------------------------------------------------------------------------
        | stok_logs_toko — qty, stok_sebelum, stok_sesudah
        |--------------------------------------------------------------------------
        */
        Schema::table('stok_logs_toko', function (Blueprint $table) {
            $table->decimal('qty', 15, 2)->change();
            $table->decimal('stok_sebelum', 15, 2)->change();
            $table->decimal('stok_sesudah', 15, 2)->change();
        });

        /*
        |--------------------------------------------------------------------------
        | detail_surat_jalan — qty_kirim, qty_diterima
        |--------------------------------------------------------------------------
        */
        Schema::table('detail_surat_jalan', function (Blueprint $table) {
            $table->decimal('qty_kirim', 15, 2)->change();
            $table->decimal('qty_diterima', 15, 2)->default(0)->change();
        });

        /*
        |--------------------------------------------------------------------------
        | penjualan_return_detail — qty
        |--------------------------------------------------------------------------
        */
        Schema::table('penjualan_return_detail', function (Blueprint $table) {
            $table->decimal('qty', 15, 2)->change();
        });

        // Aktifkan kembali strict mode ke default MySQL/Laravel
        DB::statement("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    }

    /**
     * Rollback — kembalikan semua kolom ke integer.
     */
    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        Schema::table('barangs', function (Blueprint $table) {
            $table->integer('stok_minimum')->default(0)->change();
        });

        Schema::table('penjualan_details', function (Blueprint $table) {
            $table->integer('qty')->change();
            $table->integer('potongan')->nullable()->change();
        });

        Schema::table('stok_barang_toko', function (Blueprint $table) {
            $table->integer('stok')->default(0)->change();
        });

        Schema::table('stok_logs_toko', function (Blueprint $table) {
            $table->integer('qty')->change();
            $table->integer('stok_sebelum')->change();
            $table->integer('stok_sesudah')->change();
        });

        Schema::table('detail_surat_jalan', function (Blueprint $table) {
            $table->integer('qty_kirim')->change();
            $table->integer('qty_diterima')->default(0)->change();
        });

        Schema::table('penjualan_return_detail', function (Blueprint $table) {
            $table->integer('qty')->change();
        });

        DB::statement("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    }
};