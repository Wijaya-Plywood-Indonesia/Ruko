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
        Schema::create('produksi_telurs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->integer('jumlah_telur_butir')->default(0);
            $table->decimal('jumlah_telur_kilo', 8, 2)->default(0);
            $table->integer('jumlah_telur_tray')->default(0);
            $table->boolean('is_validated')->default(false);
            $table->string('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksi_telurs');
    }
};
