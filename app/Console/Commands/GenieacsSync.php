<?php

namespace App\Console\Commands;

use App\Services\GenieacsService;
use Illuminate\Console\Command;

class GenieacsSync extends Command
{
    protected $signature = 'genieacs:sync {--limit=200}';
    protected $description = 'Sync ONU device list from GenieACS NBI';

    public function handle(GenieacsService $genieacs): int
    {
        try {
            $count = $genieacs->syncDevices((int) $this->option('limit'));
            $this->info("Synced {$count} GenieACS devices.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('GenieACS sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
