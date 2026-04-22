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
        Schema::create('induk_akuns', function (Blueprint $table) {
            $table->id();
            $table->string('kode_induk_akun')->unique();
            $table->string('nama_induk_akun')->nullable()->default('Tidak Punya Nama Akun');
            $table->text('keterangan')->nullable();
            $table->text('saldo_normal')->nullable();
            $table->string('status')->default('aktif');
            // kolom created_by (biasanya refer ke users)
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('induk_akuns');
    }
};
