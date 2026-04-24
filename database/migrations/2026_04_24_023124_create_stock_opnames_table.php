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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();

            $table->string('no_opname')->unique();  // SO-20260423-082557123456

            $table->foreignId('toko_id')
                ->constrained('identitas_toko')
                ->restrictOnDelete();

            $table->date('tanggal_opname');

            $table->enum('status', [
                'draft',      // sedang diinput
                'menunggu',   // sudah submit, menunggu approval
                'disetujui',  // disetujui, stok sudah di-adjust
                'ditolak',    // ditolak oleh approver
            ])->default('draft');

            $table->text('catatan')->nullable();           // catatan petugas
            $table->text('catatan_approval')->nullable();  // catatan approver

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });

        // ============================================================
        // Tabel detail per barang
        // ============================================================
        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_opname_id')
                ->constrained('stock_opnames')
                ->cascadeOnDelete();

            $table->foreignId('barang_id')
                ->constrained('barangs')
                ->restrictOnDelete();

            $table->decimal('stok_sistem', 15, 2)->default(0);  // stok di DB saat opname dibuat
            $table->decimal('stok_aktual', 15, 2)->nullable();  // hasil hitung fisik
            $table->decimal('selisih', 15, 2)->nullable();      // aktual - sistem (bisa negatif)

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->unique(['stock_opname_id', 'barang_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_details');
        Schema::dropIfExists('stock_opnames');
    }
};
