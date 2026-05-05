<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekening_perusahaan', function (Blueprint $table) {
            // FK ke sub_anak_akuns — lebih robust dari string no_akun
            // nullable karena rekening lama belum tentu langsung diisi
            $table->foreignId('sub_anak_akun_id')
                ->nullable()
                ->after('atas_nama')
                ->constrained('sub_anak_akuns')
                ->nullOnDelete()
                ->comment('Akun jurnal yang terhubung ke rekening ini');
        });
    }

    public function down(): void
    {
        Schema::table('rekening_perusahaan', function (Blueprint $table) {
            $table->dropForeign(['sub_anak_akun_id']);
            $table->dropColumn('sub_anak_akun_id');
        });
    }
};