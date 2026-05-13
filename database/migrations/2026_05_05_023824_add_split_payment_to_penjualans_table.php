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
        Schema::table('penjualans', function (Blueprint $table) {
            $table->decimal('bayar_tunai', 15, 2)->default(0)->after('bayar');
            $table->decimal('bayar_transfer', 15, 2)->default(0)->after('bayar_tunai');
            $table->string('metode_pembayaran')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->dropColumn(['bayar_tunai', 'bayar_transfer']);
            $table->enum('metode_pembayaran', ['TUNAI', 'TRANSFER'])->change();
        });
    }
};
