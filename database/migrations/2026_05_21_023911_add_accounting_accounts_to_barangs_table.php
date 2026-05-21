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
        Schema::table('barangs', function (Blueprint $table) {
            // Asumsi tabel akun Anda bernama sub_anak_akuns
            $table->foreignId('akun_pendapatan_id')->nullable()->constrained('sub_anak_akuns')->onDelete('set null');
            $table->foreignId('akun_hpp_id')->nullable()->constrained('sub_anak_akuns')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            $table->dropForeign(['akun_pendapatan_id']);
            $table->dropForeign(['akun_hpp_id']);
            $table->dropColumn(['akun_pendapatan_id', 'akun_hpp_id']);
        });
    }
};
