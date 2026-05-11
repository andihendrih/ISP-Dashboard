<?php

namespace App\Console\Commands;

use App\Services\GenieacsService;
use Illuminate\Console\Command;

class GenieacsRebuildCache extends Command
{
    protected $signature = 'genieacs:rebuild-cache';

    protected $description = 'Re-extract cache columns (pppoe_username, rx_power, wifi SSID, wan IP) from existing raw JSON';

    public function handle(GenieacsService $genieacs): int
    {
        $this->info('Rebuilding extracted fields cache...');
        $count = $genieacs->rebuildExtractedFields();
        $this->info("Done. {$count} device updated.");
        return self::SUCCESS;
    }
}
