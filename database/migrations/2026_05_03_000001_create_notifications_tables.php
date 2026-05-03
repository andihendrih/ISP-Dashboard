<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('channel', ['wa', 'email']);
            $table->string('template', 64);
            $table->string('recipient', 160);
            $table->string('subject', 200)->nullable();
            $table->text('body');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('provider_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('customer_profile_id')->nullable()->constrained('customer_profiles')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index('template');
        });

        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->boolean('enabled')->default(true);
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('notification_logs');
    }
};
