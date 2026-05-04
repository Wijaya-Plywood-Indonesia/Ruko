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
        Schema::create('produksi_pakan_mentahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_produksi_pakan')
                ->constrained('produksi_pakans')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_barang')
                ->constrained('barangs')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('stok_awal', 12, 2)->default(0)->nullable();
            $table->decimal('masuk', 12, 2)->default(0)->nullable();

            $table->decimal('keluar_pullet', 12, 2)->default(0)->nullable();
            $table->decimal('keluar_l1', 12, 2)->default(0)->nullable();
            $table->decimal('keluar_l2', 12, 2)->default(0)->nullable();

            $table->decimal('stok_akhir', 12, 2)->default(0)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksi_pakan_mentahs');
    }
};
