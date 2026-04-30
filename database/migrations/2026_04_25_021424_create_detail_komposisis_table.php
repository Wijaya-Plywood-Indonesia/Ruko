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
        Schema::create('detail_komposisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_komposisi')
                ->constrained('komposisis')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('id_barang')
                ->constrained('barangs')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->integer('kuantitas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_komposisis');
    }
};
