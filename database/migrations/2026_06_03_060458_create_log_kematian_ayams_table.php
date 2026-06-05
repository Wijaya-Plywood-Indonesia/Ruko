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
        Schema::create('log_kematian_ayams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ayam')
                ->constrained('ayams')
                ->cascadeOnDelete();
            $table->foreignId('id_kandang')
                ->constrained('kandangs');
            $table->date('tanggal');
            $table->unsignedInteger('jumlah_mati');
            $table->text('keterangan')->nullable();
            $table->string('created_by')->nullable();
            $table->boolean('is_jurnal_created')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_kematian_ayams');
    }
};
