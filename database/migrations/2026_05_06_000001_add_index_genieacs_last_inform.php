<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('genieacs_devices', function (Blueprint $t) {
            $t->index('last_inform_at', 'genieacs_devices_last_inform_idx');
            $t->index('status', 'genieacs_devices_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('genieacs_devices', function (Blueprint $t) {
            $t->dropIndex('genieacs_devices_last_inform_idx');
            $t->dropIndex('genieacs_devices_status_idx');
        });
    }
};
