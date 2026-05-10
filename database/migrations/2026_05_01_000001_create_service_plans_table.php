<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('service_type', ['pppoe', 'hotspot'])->default('pppoe');
            $table->string('rate_limit')->nullable();
            $table->string('radius_group')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_plans');
    }
};
