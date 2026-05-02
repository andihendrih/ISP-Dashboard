<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_profile_id')->constrained('customer_profiles')->cascadeOnDelete();
            $table->foreignId('service_plan_id')->nullable()->constrained('service_plans')->nullOnDelete();

            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->date('period_start');
            $table->date('period_end');

            $table->boolean('is_prorated')->default(false);
            $table->unsignedSmallInteger('days_charged')->default(0);
            $table->unsignedSmallInteger('days_in_month')->default(0);

            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);

            $table->date('due_date');
            $table->enum('status', ['belum_lunas', 'lunas', 'terlambat', 'cancelled'])->default('belum_lunas');

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['customer_profile_id', 'period_year', 'period_month'], 'inv_period_unique');
            $table->index(['status', 'due_date']);
            $table->index(['period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
