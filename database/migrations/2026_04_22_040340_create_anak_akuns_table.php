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
        Schema::create('anak_akuns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_induk_akun')
                ->constrained('induk_akuns')
                ->onDelete('cascade');

            $table->string('kode_anak_akun')->unique();
            $table->string('nama_anak_akun')->nullable()->default('Tidak Punya Nama Akun');
            $table->text('keterangan')->nullable();
            // kolom parent (jika maksudnya refer ke anak_akuns juga -> self relation)
            $table->foreignId('parent')
                ->nullable()
                ->constrained('anak_akuns')
                ->nullOnDelete();

            // kolom created_by (biasanya refer ke users)
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('saldo_normal')->nullable();
            $table->string('status')->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anak_akuns');
    }
};
