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
        Schema::table('produksi_pakans', function (Blueprint $table) {
            $table->foreignId('id_komposisi')
                ->nullable()
                ->after('id') // Menempatkan kolom setelah ID
                ->constrained('komposisis')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produksi_pakans', function (Blueprint $table) {
            $table->dropForeign(['id_komposisi']);
            $table->dropColumn('id_komposisi');
        });
    }
};
