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
        Schema::table('satuans', function (Blueprint $table) {
            $table->boolean('is_base_unit')->default(false)->after('keterangan');
        });

        Schema::create('satuan_konversis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_satuan_asal')->constrained('satuans')->cascadeOnDelete();
            $table->foreignId('id_satuan_tujuan')->constrained('satuans')->cascadeOnDelete();
            $table->decimal('nilai_konversi', 15, 6);
            $table->string('keterangan')->nullable();
            $table->foreignId('id_barang')->nullable()->constrained('barangs')->nullOnDelete();
            // Sebagai Forensi, ketika ada ketentuan yang berubah maka dari tidak rusak dikemudian hari
            /* Contoh 
            Ketika terdapat sak 25 kg ternyata sudah berubah menjadi sak 20kg maka data sebelumnya tidak akan langsung berubah.
            Dia memiliki 2 record untuk ketentuan agar sak 20 menjadi ketentuan baru yang berlaku mulai tanggal berapa.
            */
            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('satuan_konversis');

        // WAJIB: Tambahkan rollback untuk kolom is_base_unit agar database kembali ke state semula
        Schema::table('satuans', function (Blueprint $table) {
            if (Schema::hasColumn('satuans', 'is_base_unit')) {
                $table->dropColumn('is_base_unit');
            }
        });
    }
};
