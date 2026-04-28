<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genieacs_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique();   // genieacs _id
            $table->string('serial_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('product_class')->nullable();
            $table->string('model_name')->nullable();
            $table->string('software_version')->nullable();
            $table->string('hardware_version')->nullable();
            $table->string('ssid')->nullable();
            $table->string('ip')->nullable();
            $table->string('tag')->nullable();
            $table->string('status')->default('unknown'); // online/offline/unknown
            $table->timestamp('last_inform_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genieacs_devices');
    }
};
