<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant SaaS table — tiap row mewakili 1 ISP penyewa portal.
 *
 * - code  : short unique identifier (3-8 chars, lowercase) — dipakai
 *           sebagai prefix RADIUS username + tag GenieACS.
 * - slug  : URL-friendly identifier (sama dengan code by default).
 * - name  : nama display (e.g. "AHNet", "Padi Net", "Langit ISP").
 * - plan  : subscription tier ('basic', 'pro', 'enterprise').
 * - max_customers : soft limit (null = unlimited).
 * - brand_* : info tenant yg muncul di invoice/portal pelanggan tenant.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('slug', 32)->unique();
            $table->string('name', 120);
            $table->string('plan', 32)->default('basic');
            $table->unsignedInteger('max_customers')->nullable();
            $table->boolean('is_active')->default(true);

            // brand info untuk invoice/portal pelanggan tenant
            $table->string('brand_company', 120)->nullable();
            $table->string('brand_phone', 32)->nullable();
            $table->string('brand_email', 120)->nullable();
            $table->string('brand_address', 255)->nullable();
            $table->string('brand_logo_path', 255)->nullable();

            // contact person utama tenant
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('contact_email', 120)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
