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
        Schema::create('list_akun', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pegawai')
                ->constrained('pegawais')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('id_akun')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('id_toko')
                ->constrained('identitas_toko')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_akuns');
    }
};
