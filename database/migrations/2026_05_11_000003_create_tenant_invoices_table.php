<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoice subscription tenant — superadmin nagih tenant tiap bulan.
 *
 * Field:
 *  - invoice_number unique: e.g. SUB-2026-05-0001 (di-generate berurut)
 *  - period_start / period_end: range bulan yang ditagih
 *  - amount: total tagihan IDR (sudah include prorate kalau ada)
 *  - prorate_factor: 0..1, 1 = full month, <1 = prorated
 *  - status: unpaid (default), paid, overdue, cancelled
 *  - due_date: tanggal jatuh tempo
 *  - paid_at: kalo udah lunas
 *  - notes: keterangan internal
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('tenant_subscriptions')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('tenant_plans')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('prorate_factor', 5, 4)->default(1);
            $table->unsignedBigInteger('amount'); // IDR
            $table->enum('status', ['unpaid', 'paid', 'overdue', 'cancelled'])->default('unpaid');
            $table->date('due_date');
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('due_date');
            $table->index('period_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_invoices');
    }
};
