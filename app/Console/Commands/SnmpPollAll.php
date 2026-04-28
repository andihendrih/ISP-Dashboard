<?php

namespace App\Console\Commands;

use App\Models\DeviceMikrotik;
use App\Services\SnmpService;
use Illuminate\Console\Command;

class SnmpPollAll extends Command
{
    protected $signature = 'snmp:poll {--device-id=}';
    protected $description = 'Poll SNMP counters for all active Mikrotik devices and persist to snmp_logs';

    public function handle(SnmpService $snmp): int
    {
        $query = DeviceMikrotik::where('is_active', true);
        if ($id = $this->option('device-id')) {
            $query->where('id', (int) $id);
        }
        $devices = $query->get();

        if ($devices->isEmpty()) {
            $this->info('No active devices to poll.');
            return self::SUCCESS;
        }

        foreach ($devices as $device) {
            try {
                $rows = $snmp->pollDevice($device);
                $this->info("[{$device->name}] polled " . count($rows) . ' interface(s)');
            } catch (\Throwable $e) {
                $this->error("[{$device->name}] FAILED: " . $e->getMessage());
            }
        }
        return self::SUCCESS;
    }
}
