<?php

namespace App\Console\Commands;

use App\Models\TenantInvoice;
use App\Services\Notifications\Contracts\WaProvider;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Reminder tenant invoice via WhatsApp.
 *
 * Schedule reminder: H-3, H-1 (sebelum due), H+1, H+7 (sesudah due)
 * dibaca dari config('ahnet.tenant_billing.reminder_days', [-3,-1,1,7]).
 *
 * Reminder dikirim ke contact_phone PIC tenant (kalau ada).
 * Skip kalau invoice udah paid/cancelled.
 */
class TenantBillingNotify extends Command
{
    protected $signature   = 'tenant-billing:notify';
    protected $description = 'Kirim reminder WhatsApp ke PIC tenant untuk invoice yg mendekati / lewat due';

    public function handle(WaProvider $wa): int
    {
        $today   = CarbonImmutable::today();
        $days    = config('ahnet.tenant_billing.reminder_days', [-3, -1, 1, 7]);
        $sent    = 0;
        $skipped = 0;

        foreach ($days as $offset) {
            $targetDue = $today->subDays((int) $offset)->toDateString();
            // offset negatif → reminder sebelum due (e.g. -3 → due_date = today+3)
            // offset positif → reminder sesudah due (e.g. 7 → due_date = today-7)
            $targetDue = $offset >= 0
                ? $today->subDays((int) $offset)->toDateString()
                : $today->addDays((int) abs($offset))->toDateString();

            $invoices = TenantInvoice::with('tenant')
                ->whereIn('status', [TenantInvoice::STATUS_UNPAID, TenantInvoice::STATUS_OVERDUE])
                ->whereDate('due_date', $targetDue)
                ->get();

            foreach ($invoices as $inv) {
                $tenant = $inv->tenant;
                if (!$tenant || !$tenant->contact_phone) {
                    $skipped++;
                    continue;
                }
                $msg = $this->buildMessage($inv, $offset);
                try {
                    $resp = $wa->send($tenant->contact_phone, $msg);
                    if (($resp['ok'] ?? false) === true) {
                        $sent++;
                    } else {
                        Log::warning('Tenant billing reminder WA not ok', [
                            'invoice' => $inv->invoice_number,
                            'tenant'  => $tenant->code,
                            'resp'    => $resp['message'] ?? '',
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Tenant billing reminder failed', [
                        'invoice' => $inv->invoice_number,
                        'tenant'  => $tenant->code,
                        'err'     => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Tenant billing reminder: sent={$sent} skipped={$skipped}");
        return self::SUCCESS;
    }

    private function buildMessage(TenantInvoice $inv, int $offset): string
    {
        $amount = number_format($inv->amount, 0, ',', '.');
        $due    = optional($inv->due_date)->format('d M Y');
        $tname  = $inv->tenant?->name ?? '—';

        if ($offset < 0) {
            $hari = abs($offset);
            $header = "🔔 *Pengingat Tagihan*";
            $body   = "Tagihan langganan portal *{$tname}* jatuh tempo *{$hari} hari lagi* ({$due}).";
        } elseif ($offset === 0) {
            $header = "🔔 *Tagihan Jatuh Tempo Hari Ini*";
            $body   = "Tagihan langganan portal *{$tname}* jatuh tempo *hari ini* ({$due}).";
        } elseif ($offset <= 1) {
            $header = "⚠️ *Tagihan Lewat Jatuh Tempo*";
            $body   = "Tagihan langganan portal *{$tname}* sudah lewat jatuh tempo ({$due}). Mohon segera lunasi.";
        } else {
            $header = "🚨 *Akan Disuspend*";
            $body   = "Tagihan langganan portal *{$tname}* sudah {$offset} hari lewat jatuh tempo ({$due}). Tenant akan disuspend otomatis kalau gak segera lunas.";
        }

        return "{$header}\n\nNo. Invoice: {$inv->invoice_number}\nTotal: Rp {$amount}\nDue: {$due}\n\n{$body}\n\nLogin ke portal untuk upload bukti bayar.";
    }
}
