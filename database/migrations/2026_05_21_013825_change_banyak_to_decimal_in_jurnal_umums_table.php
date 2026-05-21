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
        Schema::table('jurnal_umums', function (Blueprint $table) {
            $table->decimal('banyak', 15, 4)->nullable()->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_umums', function (Blueprint $table) {
            $table->integer('banyak')->nullable()->default(1)->change();
        });
    }
};
