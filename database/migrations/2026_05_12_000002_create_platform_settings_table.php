<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            // Single-row table: tetep pakai id buat join/lookup gampang.

            // Brand platform
            $table->string('brand_name', 120)->nullable();
            $table->string('brand_logo_path', 255)->nullable();
            $table->string('brand_address', 255)->nullable();
            $table->string('brand_phone', 60)->nullable();
            $table->string('brand_email', 120)->nullable();
            $table->text('brand_tagline')->nullable();
            $table->text('bank_info')->nullable(); // multi-line: BANK|NO|NAMA

            // WhatsApp platform (untuk reminder ke tenant)
            $table->string('wa_provider', 20)->default('null');
            $table->text('wa_credentials')->nullable();

            // Email platform (untuk reminder ke tenant)
            $table->string('email_provider', 20)->default('null');
            $table->text('email_credentials')->nullable();

            $table->timestamps();
        });

        // Seed default row supaya gak perlu firstOrCreate berkali2.
        \DB::table('platform_settings')->insert([
            'brand_name' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
