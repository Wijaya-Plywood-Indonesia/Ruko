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
        Schema::create('jurnal_pembantu_headers', function (Blueprint $table) {
            $table->id();

            // ── Nomor & Tanggal ───────────────────────────────────────────────
            $table->unsignedInteger('no_jurnal_pembantu')->index();
            $table->date('tgl_transaksi')->nullable();

            // ── Jenis & Sumber ────────────────────────────────────────────────
            $table->string('jenis_transaksi', 50);
            // 'bm' | 'bk' | 'dp' | 'gaji' | 'balik' | 'lain'

            $table->string('modul_asal', 50)->nullable();
            // 'penjualan_telur' | 'retur_penjualan' | 'pembelian_pakan'
            // 'produksi_telur'  | 'penggajian'      | 'lain'

            // ── Referensi Jurnal Umum ─────────────────────────────────────────
            $table->unsignedInteger('jurnal')->index();

            // ── Akun ──────────────────────────────────────────────────────────
            $table->string('no_akun', 20);
            $table->string('nama_akun', 255)->nullable();
            $table->enum('map', ['d', 'k']);

            // ── Keterangan & Dokumen ──────────────────────────────────────────
            $table->text('keterangan');
            $table->string('no_dokumen', 100)->nullable();
            $table->text('catatan_internal')->nullable();

            // ── Nilai ─────────────────────────────────────────────────────────
            $table->decimal('total_nilai', 20, 4)->default(0);

            // ── Status ────────────────────────────────────────────────────────
            $table->string('status', 20)->default('draft')->index();
            // 'draft' → 'diposting' → 'dibalik' | 'dibatalkan'

            // ── Jurnal Balik ──────────────────────────────────────────────────
            $table->boolean('adalah_jurnal_balik')->default(false);
            $table->foreignId('membalik_id')
                ->nullable()
                ->constrained('jurnal_pembantu_headers')
                ->nullOnDelete();

            // ── Audit Trail ───────────────────────────────────────────────────
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->foreignId('diubah_oleh')->nullable()->constrained('users');
            $table->foreignId('diposting_oleh')->nullable()->constrained('users');
            $table->timestamp('tgl_posting')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_pembantu_headers');
    }
};
