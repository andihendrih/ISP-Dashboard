<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subscription: link tenant ↔ plan, plus billing cycle state.
 *
 * Aturan main:
 *  - 1 tenant punya 1 active subscription (status='active').
 *  - 'started_at' tanggal subscription mulai (untuk prorate first invoice)
 *  - 'next_billing_at' tanggal next invoice generated (cron monthly)
 *  - 'cancelled_at' kalau tenant unsubscribe, plan tetep aktif sampai
 *    period saat ini selesai
 *  - 'price_override' nullable: kalo non-null, override price plan
 *    (e.g. discount khusus tenant tertentu)
 *  - 'grace_days_override' nullable: override default 7 hari grace period
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('tenant_plans')->restrictOnDelete();
            $table->enum('status', ['trial', 'active', 'past_due', 'cancelled', 'suspended'])->default('active');
            $table->date('started_at');
            $table->date('next_billing_at');
            $table->date('cancelled_at')->nullable();
            $table->date('suspended_at')->nullable();
            $table->unsignedBigInteger('price_override')->nullable();
            $table->unsignedSmallInteger('grace_days_override')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('next_billing_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
