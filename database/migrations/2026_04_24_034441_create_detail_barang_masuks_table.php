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
        Schema::create('detail_barang_masuks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_barang_masuk')
                ->constrained('barang_masuks')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_barang')
                ->constrained('barangs')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->integer('kuantitas')->nullable();
            $table->integer('harga_satuan')->nullable();
            $table->integer('sub_total')->nullable();

            $table->string('created_by')->nullable();
            $table->string('validated_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_barang_masuks');
    }
};
