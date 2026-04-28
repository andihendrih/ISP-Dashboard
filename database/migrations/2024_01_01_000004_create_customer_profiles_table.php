<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('id_card_number')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('package')->nullable();          // referenced radius group
            $table->string('rate_limit')->nullable();        // e.g. 10M/10M
            $table->enum('status', ['active', 'isolir', 'free', 'pending', 'inactive'])->default('pending');
            $table->enum('service_type', ['pppoe', 'hotspot'])->default('pppoe');
            $table->string('radius_username')->nullable()->index();
            $table->foreignId('mikrotik_device_id')->nullable()->constrained('devices_mikrotik')->nullOnDelete();
            $table->date('joined_at')->nullable();
            $table->date('expired_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'service_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
