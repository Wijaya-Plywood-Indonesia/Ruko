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
        Schema::create('jurnal_pembantu_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('jurnal_pembantu_header_id')
                ->constrained('jurnal_pembantu_headers')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('urut')->default(1);

            // ── Identitas Pihak ───────────────────────────────────────────────
            $table->string('jenis_pihak', 20)->nullable();
            // 'pelanggan' | 'pemasok' | 'karyawan' | 'lain'

            $table->string('nama_pihak', 255)->nullable();

            // ── Identitas Barang ──────────────────────────────────────────────
            $table->string('nama_barang', 255)->nullable();
            // Contoh: Telur Ayam Grade A, Telur Omega 3, Pakan Ayam, dll.

            $table->string('no_dokumen', 100)->nullable();
            $table->string('no_referensi', 100)->nullable();
            $table->text('keterangan')->nullable();

            // ── Kuantitas & Nilai ─────────────────────────────────────────────
            $table->decimal('banyak', 12, 4)->nullable();
            // Jumlah unit / kg / karton / butir / dll.

            $table->decimal('harga', 20, 4)->default(0);
            // Harga per satuan

            $table->decimal('jumlah', 20, 4)->nullable();
            // Hasil otomatis: banyak × harga (dikalkulasi di service/observer)

            // ── Status ────────────────────────────────────────────────────────
            $table->boolean('status')->default(1);

            // ── Audit ─────────────────────────────────────────────────────────
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_pembantu_items');
    }
};
