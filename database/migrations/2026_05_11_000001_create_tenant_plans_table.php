<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel master plan tenant: basic / pro / enterprise (atau custom).
 * Tenant subscribe ke salah satu plan, tenant_subscriptions.plan_id → ini.
 *
 * Field 'features' JSON simpen flag fitur per plan biar gampang extend:
 *   { "max_devices": 200, "support_priority": "standard", "white_label": false }
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique(); // basic, pro, enterprise, custom-xxx
            $table->string('name', 80);           // display name
            $table->unsignedBigInteger('price');  // IDR per bulan
            $table->unsignedInteger('max_customers')->nullable(); // null = unlimited
            $table->unsignedInteger('max_devices')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plans');
    }
};
