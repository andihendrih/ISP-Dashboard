<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotspot_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();          // username == password
            $table->string('batch_id')->nullable()->index();
            $table->string('profile')->nullable();          // radius group
            $table->string('rate_limit')->nullable();
            $table->unsignedInteger('valid_minutes')->nullable();   // session-timeout minutes
            $table->enum('status', ['unused', 'used', 'expired'])->default('unused');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_vouchers');
    }
};
