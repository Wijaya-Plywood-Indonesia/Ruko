<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan tidak ada NULL sebelum alter
        DB::table('penjualans')
            ->whereNull('keterangan')
            ->update(['keterangan' => '']);

        // Paksa ubah tipe kolom
        DB::statement("
            ALTER TABLE penjualans 
            MODIFY keterangan VARCHAR(255) NULL
        ");
    }

    public function down(): void
    {
        // Jika rollback, ubah balik ke integer
        DB::statement("
            ALTER TABLE penjualans 
            MODIFY keterangan INT NULL
        ");
    }
};
