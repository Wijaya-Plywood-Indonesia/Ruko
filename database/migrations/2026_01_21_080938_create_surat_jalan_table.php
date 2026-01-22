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
        Schema::create('surat_jalan', function (Blueprint $table) {
            $table->id();
            //header Surat
            $table->string('no_surat_jalan')->unique();
            $table->date('tanggal_kirim');

            $table->foreignId('toko_asal_id')   // gudang pusat
                ->constrained('identitas_toko')
                ->restrictOnDelete();

            $table->foreignId('toko_tujuan_id') // toko ecer
                ->constrained('identitas_toko')
                ->restrictOnDelete();

            $table->enum('status', [
                'draft',
                'dikirim',
                'diterima',
                'ditolak'
            ])->default('draft');

            $table->text('keterangan')->nullable();

            //identitas kendaraan
            $table->string('nama_supir')->nullable();
            $table->string('jeniskendaraan')->nullable();
            $table->string('plat')->nullable();

            //pembuat dan validator
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('validated_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_jalan');
    }
};
