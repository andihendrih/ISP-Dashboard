<?php

namespace App\Console\Commands;

use App\Services\TenantBillingService;
use Illuminate\Console\Command;

class TenantBillingAutoSuspend extends Command
{
    protected $signature = 'tenant-billing:auto-suspend';
    protected $description = 'Auto-suspend tenant yang overdue melewati grace_days';

    public function handle(TenantBillingService $svc): int
    {
        $n = $svc->autoSuspendOverdue();
        $this->info("Auto-suspended {$n} tenant(s).");
        return self::SUCCESS;
    }
}
