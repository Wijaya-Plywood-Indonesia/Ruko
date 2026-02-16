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
        /*
        |--------------------------------------------------------------------------
        | Tabel Penjualan (Header / Nota)
        |--------------------------------------------------------------------------
        */
        Schema::create('penjualan_return', function (Blueprint $table) {
            $table->id();

            // Nota
            $table->string('no_nota')->unique();
            $table->dateTime('tanggal');

            // Customer (SNAPSHOT)
            $table->string('nama_customer');
            $table->boolean('is_member');
            $table->text('alamat')->nullable();

            // Pembayaran
            $table->enum('metode_pembayaran', ['TUNAI', 'TRANSFER']);
            $table->string('bank')->nullable();
            $table->string('no_rekening')->nullable();

            // Pengiriman / Surat Jalan
            $table->string('kendaraan')->nullable();
            $table->string('plat_kendaraan')->nullable();
            $table->string('nama_sopir')->nullable();

            // Keuangan
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('bayar', 15, 2)->default(0);
            $table->decimal('kembalian', 15, 2)->default(0);

            // Keterangan & Status
            $table->text('keterangan')->nullable();
            $table->enum('status_return', ['DIPROSES', 'DITOLAK', 'DITERIMA', 'SELESAI', 'PENDING' ]);
            // $table->enum('tipe_return', ['REFUND', 'REPLACE', 'REPAIR' ]);
            // $table->enum('lokasi_barang', ['CUSTOMER', 'TOKO', 'GUDANG', 'PABRIK' ]);

            // Validate 
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('validate_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
                

            // Audit
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan_return');
    }
};
