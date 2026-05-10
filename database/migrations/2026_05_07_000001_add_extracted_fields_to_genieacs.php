<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('genieacs_devices', function (Blueprint $t) {
            $t->string('pppoe_username', 128)->nullable()->after('ip');
            $t->decimal('rx_power', 6, 2)->nullable()->after('pppoe_username');
            $t->string('wifi_ssid_24', 64)->nullable()->after('rx_power');
            $t->string('wifi_ssid_5g', 64)->nullable()->after('wifi_ssid_24');
            $t->string('wan_external_ip', 64)->nullable()->after('wifi_ssid_5g');

            $t->index('pppoe_username', 'genieacs_devices_pppoe_idx');
            $t->index('product_class', 'genieacs_devices_product_idx');
            $t->index(['status', 'last_inform_at'], 'genieacs_devices_status_inform_idx');
        });
    }

    public function down(): void
    {
        Schema::table('genieacs_devices', function (Blueprint $t) {
            $t->dropIndex('genieacs_devices_pppoe_idx');
            $t->dropIndex('genieacs_devices_product_idx');
            $t->dropIndex('genieacs_devices_status_inform_idx');
            $t->dropColumn(['pppoe_username', 'rx_power', 'wifi_ssid_24', 'wifi_ssid_5g', 'wan_external_ip']);
        });
    }
};
