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
            $table->foreignId('id_kandang')
                ->constrained('kandangs');
            $table->date('tanggal');
            $table->unsignedInteger('jumlah_telur_butir');
            $table->unsignedInteger('jumlah_telur_retak')->default(0);
            $table->unsignedInteger('jumlah_telur_pecah')->default(0);
            $table->unsignedInteger('jumlah_ayam_mati')->default(0); // input bersamaan
            $table->decimal('hen_day_production', 5, 2)->nullable();
            $table->string('created_by')->nullable();
            $table->string('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['id_kandang', 'tanggal']);
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
