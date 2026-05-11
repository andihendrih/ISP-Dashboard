<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembayaran tenant atas invoice subscription.
 *
 * 1 invoice bisa punya banyak payment (kalo nyicil). Sum amount payment
 * yg confirmed >= invoice.amount → invoice ditandai 'paid'.
 *
 * Workflow:
 *  1. Tenant transfer manual → upload bukti
 *  2. Insert row di sini dengan status='pending', proof_path filled
 *  3. Superadmin verify → set status='confirmed', confirmed_by, confirmed_at
 *  4. Trigger invoice status update (paid kalau total terpenuhi)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('tenant_invoices')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('amount'); // IDR
            $table->enum('method', ['bank_transfer', 'va', 'ewallet', 'cash', 'other'])->default('bank_transfer');
            $table->string('reference', 120)->nullable(); // no. ref bank / VA / dll
            $table->date('transferred_at')->nullable();   // tanggal transfer
            $table->string('proof_path', 255)->nullable(); // path upload bukti
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('confirmed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payments');
    }
};
