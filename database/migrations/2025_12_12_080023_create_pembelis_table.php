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
        Schema::create('pembelis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
        // ============================
        // 2. TABEL REKENING PEMBELI
        // ============================
        Schema::create('rekening_pembelis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pembeli_id')
                ->constrained('pembelis')
                ->cascadeOnDelete();

            $table->enum('jenis', ['BANK', 'EWALLET'])
                ->default('BANK');

            // BANK (BCA, BRI, Mandiri, dll)
            $table->string('nama_bank')->nullable();

            // EWALLET (Dana, OVO, Gopay)
            $table->string('nama_ewallet')->nullable();

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
        Schema::dropIfExists('rekening_pembelis');
        Schema::dropIfExists('pembelis');
    }
};
