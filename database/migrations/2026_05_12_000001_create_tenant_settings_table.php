<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings per-tenant: brand info, WhatsApp creds, SMTP, payment gateway.
 * Credential field di-encrypt via Laravel Crypt di model getter/setter.
 *
 * 1:1 ke tenants (unique tenant_id).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();

            // Brand
            $table->string('brand_name', 120)->nullable();
            $table->string('brand_logo_path', 255)->nullable();
            $table->string('brand_address', 255)->nullable();
            $table->string('brand_phone', 60)->nullable();
            $table->string('brand_email', 120)->nullable();
            $table->string('brand_npwp', 30)->nullable();
            $table->text('brand_bank_info')->nullable();   // free text: BCA xxx a/n ...

            // WhatsApp (provider + encrypted creds)
            $table->string('wa_provider', 20)->default('null'); // fonnte, cloudapi, null
            $table->text('wa_credentials')->nullable();          // encrypted JSON

            // Email (SMTP)
            $table->string('email_provider', 20)->default('null'); // smtp, sendgrid, mailgun, null
            $table->text('email_credentials')->nullable();          // encrypted JSON {host,port,username,password,from_email,from_name,encryption}

            // Payment gateway (multi: midtrans, xendit, tripay, manual)
            $table->string('payment_gateway_default', 20)->default('manual'); // midtrans/xendit/tripay/manual
            $table->text('payment_credentials')->nullable();                  // encrypted JSON {midtrans:{...},xendit:{...},tripay:{...},manual:{bank_accounts:[]}}
            $table->boolean('payment_midtrans_enabled')->default(false);
            $table->boolean('payment_xendit_enabled')->default(false);
            $table->boolean('payment_tripay_enabled')->default(false);
            $table->boolean('payment_manual_enabled')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
