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
        Schema::create('identitas_toko', function (Blueprint $table) {
            $table->id();
            $table->string('kode_toko')->unique();
            $table->string('nama_toko');
            $table->string('pemilik')->nullable();
            $table->text('alamat')->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('email')->nullable();

            $table->enum('status', ['aktif', 'nonaktif'])
                ->default('aktif');

            $table->text('keterangan')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identitas_toko');
    }
};
