<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('surat_jalan', function (Blueprint $table) {
            // validated_by diisi nanti saat barang diterima, bukan saat create
            $table->foreignId('validated_by')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('surat_jalan', function (Blueprint $table) {
            $table->foreignId('validated_by')
                ->nullable(false)
                ->change();
        });
    }
};