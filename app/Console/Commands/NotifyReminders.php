<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\Notifications\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotifyReminders extends Command
{
    protected $signature = 'notify:reminders {--channel=wa,email : Channel default}';
    protected $description = 'Kirim reminder pembayaran (H-3, H-0, overdue) ke pelanggan dengan invoice belum lunas.';

    public function handle(NotificationService $svc): int
    {
        $channels = explode(',', (string) $this->option('channel'));
        $today = Carbon::today();

        $h3   = $today->copy()->addDays(3);
        $h0   = $today;
        $h3Ov = $today->copy()->subDays(3);

        $sent = 0;
        $sent += $this->dispatchByDue($svc, 'reminder_h3', $h3,   $channels);
        $sent += $this->dispatchByDue($svc, 'reminder_h0', $h0,   $channels);
        $sent += $this->dispatchByDue($svc, 'reminder_overdue', $h3Ov, $channels);

        $this->info("Reminders dispatched: {$sent}");
        return self::SUCCESS;
    }

    private function dispatchByDue(NotificationService $svc, string $template, Carbon $due, array $channels): int
    {
        $count = 0;
        Invoice::query()
            ->with('customer')
            ->whereDate('due_date', $due->toDateString())
            ->whereIn('status', [Invoice::STATUS_BELUM_LUNAS, Invoice::STATUS_TERLAMBAT])
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->chunk(100, function ($rows) use ($svc, $template, $channels, &$count) {
                foreach ($rows as $invoice) {
                    if (!$invoice->customer) continue;
                    $logs = $svc->dispatch($template, $invoice->customer, $invoice, $channels);
                    $count += collect($logs)->where('status', 'sent')->count();
                }
            });

        return $count;
    }
}
