<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices_inventory', function (Blueprint $t) {
            $t->id();
            $t->enum('type', ['onu', 'router', 'switch', 'ap', 'radio', 'cable', 'other'])->default('onu');
            $t->string('brand')->nullable();
            $t->string('model')->nullable();
            $t->string('serial_number')->unique();
            $t->string('mac_address', 32)->nullable()->index();
            $t->enum('status', ['stock', 'assigned', 'rusak', 'hilang', 'retired'])->default('stock')->index();
            $t->decimal('purchase_price', 12, 2)->nullable();
            $t->date('purchased_at')->nullable();
            $t->string('warehouse_location')->nullable();
            $t->foreignId('customer_id')->nullable()->constrained('customer_profiles')->nullOnDelete();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices_inventory');
    }
};
