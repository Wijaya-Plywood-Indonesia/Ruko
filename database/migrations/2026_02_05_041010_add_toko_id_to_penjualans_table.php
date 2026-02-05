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
            //
            $table->unsignedBigInteger('toko_id')->nullable()->after('user_id');

            // Tambah foreign key
            $table->foreign('toko_id')
                ->references('id')
                ->on('identitas_toko')
                ->onDelete('set null'); // jika toko dihapus, nilai toko_id menjadi null

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            // 
            $table->dropForeign(['toko_id']);
            $table->dropColumn('toko_id');
        });
    }
};
