<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'NO_ZERO_IN_DATE',''))");
        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'NO_ZERO_DATE',''))");
        Schema::table('barangs', function (Blueprint $table) {
            $table->foreignId('id_sub_anak_akun')
                ->nullable()
                ->after('nama_barang')
                ->constrained('sub_anak_akuns')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->dropColumn('id_sub_anak_akun');
        });
    }
};
