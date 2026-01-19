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
        Schema::create('rekening_perusahaan', function (Blueprint $table) {
            $table->id();

            $table->string('pemilik_rekening')->nullable();

            // BANK (BCA, BRI, Mandiri, dll)
            $table->string('nama_bank')->nullable();

            // Nomor rekening atau nomor e-wallet
            $table->string('no_rekening')->nullable();

            // Atas nama rekening
            $table->string('atas_nama')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekening_perusahaan');
    }
};
