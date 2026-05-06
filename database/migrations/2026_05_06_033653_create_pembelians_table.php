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
        Schema::create('pembelians', function (Blueprint $table) {
            $table->id();

            //For validation role
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_validasi')->nullable();
            //For Document
            $table->string('nomor_nota')->nullable();
            $table->date('tanggal')->nullable();
            $table->json('foto')->nullable();

            //for snapshoot supplier
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_name');
            $table->string('supplier_phone')->nullable();
            $table->text('supplier_address')->nullable();
            $table->string('supplier_npwp')->nullable();

            //for keuangan
            $table->decimal('sub_total', 18, 2)->default(0);
            $table->decimal('total_diskon', 18, 2)->default(0);
            $table->decimal('total_ppn', 18, 2)->default(0);
            $table->decimal('ongkir', 18, 2)->default(0);
            $table->decimal('biaya_lain', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);

            //pembayaran
            $table->string('status')->default('draft')->index();

            //notes
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelians');
    }
};
