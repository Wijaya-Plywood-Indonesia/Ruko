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
        Schema::create('sub_anak_akuns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_anak_akun')
                ->constrained('anak_akuns')
                ->onDelete('cascade');
            $table->string('kode_sub_anak_akun')->unique();
            $table->string('nama_sub_anak_akun')->nullable()->default('Tidak Punya Nama Akun');
            $table->text('keterangan')->nullable();
            $table->string('status')->default('aktif');
            $table->string('saldo_normal')->nullable();
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
        Schema::dropIfExists('sub_anak_akuns');
    }
};
