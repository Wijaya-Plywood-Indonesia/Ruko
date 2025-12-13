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
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            // Identitas dasar
            $table->string('nik')->unique()->nullable(); // Nomor induk pegawai
            $table->string('nama_lengkap');
            $table->string('nama_panggilan');
            $table->enum('jenis_kelamin', ['L', 'P']); // Laki-laki / Perempuan

            // Tanggal penting
            $table->date('tanggal_lahir')->nullable();
            $table->date('tanggal_masuk')->nullable();

            // Kontak
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();

            // Alamat
            $table->text('alamat')->nullable();
            //Foto
            $table->text('foto_pegawai')->nullable();
            $table->text('foto_ktp')->nullable();

            // Status pegawai
            $table->enum('status', ['AKTIF', 'NONAKTIF'])->default('AKTIF');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
