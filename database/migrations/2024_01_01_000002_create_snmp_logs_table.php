<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snmp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices_mikrotik')->cascadeOnDelete();
            $table->unsignedInteger('if_index');
            $table->string('if_name');
            $table->unsignedBigInteger('in_octets')->default(0);
            $table->unsignedBigInteger('out_octets')->default(0);
            $table->unsignedBigInteger('in_bps')->default(0);   // computed bits/sec
            $table->unsignedBigInteger('out_bps')->default(0);
            $table->boolean('oper_status')->default(false);
            $table->timestamp('polled_at')->index();
            $table->timestamps();

            $table->index(['device_id', 'if_index', 'polled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snmp_logs');
    }
};
