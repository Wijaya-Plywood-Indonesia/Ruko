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
        Schema::create('akun_groups', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // Nama grup (Aktiva Lancar, Aktiva Tetap, dll)

            // Grup bisa punya parent (opsional)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('akun_groups')
                ->nullOnDelete();

            // Urutan tampil di laporan
            $table->integer('order')->default(0);

            $table->boolean('hidden')->default(false);

            $table->timestamps();
        });
        Schema::create('akun_group_anak_akun', function (Blueprint $table) {
            $table->id();

            $table->foreignId('akun_group_id')
                ->constrained('akun_groups')
                ->onDelete('cascade');

            $table->foreignId('anak_akun_id')
                ->constrained('anak_akuns')
                ->onDelete('cascade');

            // Untuk mencegah duplikat assign
            $table->unique(['akun_group_id', 'anak_akun_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('akun_group_anak_akun');
        Schema::dropIfExists('akun_groups');

        Schema::enableForeignKeyConstraints();
    }
};
