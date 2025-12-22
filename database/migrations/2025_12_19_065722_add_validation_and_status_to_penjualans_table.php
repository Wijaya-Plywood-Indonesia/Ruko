<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            // Validator (bukan kasir)
            $table->foreignId('validated_by')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();

            // Plat kendaraan pengirim
            $table->string('plat_kendaraan', 15)
                ->nullable()
                ->after('kendaraan');

            // Status pembayaran
            $table->string('status_transaksi', 255)
                ->default('BELUM DIBAYAR')
                ->nullable()
                ->after('kembalian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            //
            $table->dropForeign(['validated_by']);
            $table->dropColumn([
                'validated_by',
                'plat_kendaraan',
                'status_pembayaran',
            ]);
        });
    }
};
