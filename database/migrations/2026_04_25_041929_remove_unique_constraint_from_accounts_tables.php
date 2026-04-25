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
        // 1. Hapus unique di tabel induk_akuns
        Schema::table('induk_akuns', function (Blueprint $table) {
            $table->dropUnique(['kode_induk_akun']);
        });

        // 2. Hapus unique di tabel anak_akuns
        Schema::table('anak_akuns', function (Blueprint $table) {
            $table->dropUnique(['kode_anak_akun']);
        });

        // 3. Hapus unique di tabel sub_anak_akuns
        Schema::table('sub_anak_akuns', function (Blueprint $table) {
            $table->dropUnique(['kode_sub_anak_akun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan unique jika migrasi di-rollback
        Schema::table('induk_akuns', function (Blueprint $table) {
            $table->unique('kode_induk_akun');
        });

        Schema::table('anak_akuns', function (Blueprint $table) {
            $table->unique('kode_anak_akun');
        });

        Schema::table('sub_anak_akuns', function (Blueprint $table) {
            $table->unique('kode_sub_anak_akun');
        });
    }
};