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
            $table->boolean('is_member')
                ->default(false)   // penting supaya tidak NULL
                ->after('nama_customer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            //

            $table->dropColumn('is_member');

        });
    }
};
