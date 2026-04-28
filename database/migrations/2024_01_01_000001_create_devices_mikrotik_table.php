<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices_mikrotik', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host');
            $table->unsignedSmallInteger('api_port')->default(8728);
            $table->string('username');
            $table->string('password');
            $table->boolean('use_ssl')->default(false);
            $table->string('snmp_community')->default('public');
            $table->string('identity')->nullable();
            $table->string('board_name')->nullable();
            $table->string('version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['host', 'api_port']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices_mikrotik');
    }
};
