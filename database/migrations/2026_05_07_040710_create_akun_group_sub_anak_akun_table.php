<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akun_group_sub_anak_akun', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akun_group_id')
                ->constrained('akun_groups')
                ->cascadeOnDelete();
            $table->foreignId('sub_anak_akun_id')
                ->constrained('sub_anak_akuns')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['akun_group_id', 'sub_anak_akun_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akun_group_sub_anak_akun');
    }
};