<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_produksi_telurs', function (Blueprint $table) {
            // Drop foreign key first because the unique index is used by it
            $table->dropForeign('detail_produksi_telurs_id_produksi_telur_foreign');
            
            // Now we can drop the unique index
            $table->dropUnique('unique_produksi_kandang_pakan');
            
            // Re-create the foreign key, which will automatically create a new index for id_produksi_telur
            $table->foreign('id_produksi_telur')
                  ->references('id')
                  ->on('produksi_telurs')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_produksi_telurs', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign('detail_produksi_telurs_id_produksi_telur_foreign');
            
            // Re-create the unique index
            $table->unique(
                ['id_produksi_telur', 'id_kandang', 'id_produksi_pakan_campuran'],
                'unique_produksi_kandang_pakan'
            );
            
            // Re-create the foreign key
            $table->foreign('id_produksi_telur')
                  ->references('id')
                  ->on('produksi_telurs')
                  ->cascadeOnDelete();
        });
    }
};
