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
        Schema::create('detail_produksi_telurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_produksi_telur')->constrained('produksi_telurs')->cascadeOnDelete();
            $table->foreignId('id_kandang')->constrained('kandangs')->cascadeOnDelete();
            $table->foreignId('id_produksi_pakan_campuran')->nullable()->constrained('produksi_pakan_campurans')->nullOnDelete();
            $table->integer('jumlah_telur_butir')->default(0);
            $table->decimal('jumlah_telur_kilo', 8, 2)->default(0);
            $table->integer('jumlah_telur_tray')->default(0);
            $table->timestamps();

            $table->unique(
                ['id_produksi_telur', 'id_kandang', 'id_produksi_pakan_campuran'],
                'unique_produksi_kandang_pakan'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_produksi_telurs');
    }
};
