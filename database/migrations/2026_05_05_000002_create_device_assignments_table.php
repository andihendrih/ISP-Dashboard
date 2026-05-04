<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('device_id')->constrained('devices_inventory')->cascadeOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('customer_profiles')->nullOnDelete();
            $t->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $t->enum('action', ['install', 'return', 'repair', 'swap', 'retire', 'mark_stock', 'mark_lost'])->index();
            $t->timestamp('acted_at')->useCurrent();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_assignments');
    }
};
