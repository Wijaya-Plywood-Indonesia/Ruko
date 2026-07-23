<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_telurs', function (Blueprint $table) {
            $table->decimal('hasil_peti', 12, 2)
                ->default(0)
                ->after('is_validated');

            $table->decimal('hasil_kiloan', 12, 2)
                ->default(0)
                ->after('hasil_peti');

            $table->decimal('hasil_sisa', 12, 2)
                ->default(0)
                ->after('hasil_kiloan');

            $table->decimal('hasil_bentes', 12, 2)
                ->default(0)
                ->after('hasil_sisa');
        });
    }

    public function down(): void
    {
        Schema::table('produksi_telurs', function (Blueprint $table) {
            $table->dropColumn([
                'hasil_peti',
                'hasil_kiloan',
                'hasil_sisa',
                'hasil_bentes',
            ]);
        });
    }
};
