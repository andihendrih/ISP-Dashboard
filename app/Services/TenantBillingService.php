<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\TenantPlan;
use App\Models\TenantSubscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk billing SaaS — superadmin nagih tenant.
 *
 * Beda dari InvoiceService (yg ada di /app/Services) yang ngurus tagihan
 * tenant ke pelanggan-nya. Ini layer di atas: lo (platform owner) nagih
 * tenant yang nyewa portal lo.
 */
class TenantBillingService
{
    /**
     * Subscribe tenant ke plan tertentu.
     *  - Bikin TenantSubscription baru (active)
     *  - Auto-generate invoice pertama (prorated kalau mid-month)
     *  - started_at = today, next_billing_at = tanggal 1 bulan berikutnya
     */
    public function subscribe(Tenant $tenant, TenantPlan $plan, ?int $priceOverride = null, ?int $graceDaysOverride = null): TenantSubscription
    {
        return DB::transaction(function () use ($tenant, $plan, $priceOverride, $graceDaysOverride) {
            // Cancel subscription lama kalau ada
            TenantSubscription::where('tenant_id', $tenant->id)
                ->whereIn('status', [
                    TenantSubscription::STATUS_TRIAL,
                    TenantSubscription::STATUS_ACTIVE,
                    TenantSubscription::STATUS_PAST_DUE,
                ])
                ->update([
                    'status'       => TenantSubscription::STATUS_CANCELLED,
                    'cancelled_at' => now()->toDateString(),
                ]);

            $today      = CarbonImmutable::today();
            $cycleDay   = (int) config('ahnet.tenant_billing.cycle_day', 1);
            // next_billing_at: tanggal cycle bulan berikutnya
            $nextBill   = $today->day($cycleDay)->addMonth()->startOfDay();
            if ($today->day < $cycleDay) {
                // Belum lewat tanggal cycle bulan ini → next billing bulan ini juga
                $nextBill = $today->day($cycleDay);
            }

            $sub = TenantSubscription::create([
                'tenant_id'           => $tenant->id,
                'plan_id'             => $plan->id,
                'status'              => TenantSubscription::STATUS_ACTIVE,
                'started_at'          => $today->toDateString(),
                'next_billing_at'     => $nextBill->toDateString(),
                'price_override'      => $priceOverride,
                'grace_days_override' => $graceDaysOverride,
            ]);

            // Generate invoice pertama (prorated kalau started_at mid-month)
            $this->generateInvoiceForSubscription($sub, $today->toDateString(), $nextBill->subDay()->toDateString(), $proratedFromToday = true);

            return $sub->fresh(['plan', 'tenant']);
        });
    }

    /**
     * Generate invoice untuk subscription pada periode tertentu.
     *
     * @param TenantSubscription $sub
     * @param string $periodStart YYYY-MM-DD
     * @param string $periodEnd   YYYY-MM-DD
     * @param bool $proratedFromToday true = prorate kalau periodStart bukan tanggal cycle
     */
    public function generateInvoiceForSubscription(TenantSubscription $sub, string $periodStart, string $periodEnd, bool $proratedFromToday = false): TenantInvoice
    {
        $start = CarbonImmutable::parse($periodStart);
        $end   = CarbonImmutable::parse($periodEnd);

        $basePrice = $sub->effectivePrice();

        // Prorate: kalau periodStart bukan tanggal cycle, hitung pro-rata
        // dari sisa hari sampai periodEnd dibagi total hari di bulan tsb.
        $proratFactor = 1.0;
        if ($proratedFromToday) {
            $daysInMonth = $start->daysInMonth;
            $daysRemaining = $start->diffInDays($end) + 1;
            $proratFactor = min(1.0, round($daysRemaining / $daysInMonth, 4));
        }

        $amount = (int) round($basePrice * $proratFactor);

        // Due date default = 7 hari setelah invoice issued
        $dueDate = CarbonImmutable::today()->addDays(7);

        $number = $this->nextInvoiceNumber();

        return TenantInvoice::create([
            'invoice_number'  => $number,
            'tenant_id'       => $sub->tenant_id,
            'subscription_id' => $sub->id,
            'plan_id'         => $sub->plan_id,
            'period_start'    => $start->toDateString(),
            'period_end'      => $end->toDateString(),
            'prorate_factor'  => $proratFactor,
            'amount'          => $amount,
            'status'          => TenantInvoice::STATUS_UNPAID,
            'due_date'        => $dueDate->toDateString(),
        ]);
    }

    /** Generate monthly invoice untuk semua subscription yang due (cron tanggal cycle_day). */
    public function generateMonthlyInvoices(): int
    {
        $today    = CarbonImmutable::today();
        $cycleDay = (int) config('ahnet.tenant_billing.cycle_day', 1);
        $count    = 0;

        $subs = TenantSubscription::query()
            ->whereIn('status', [TenantSubscription::STATUS_ACTIVE, TenantSubscription::STATUS_PAST_DUE])
            ->whereDate('next_billing_at', '<=', $today)
            ->get();

        foreach ($subs as $sub) {
            $start = CarbonImmutable::parse($sub->next_billing_at);
            $end   = $start->addMonth()->subDay();
            $this->generateInvoiceForSubscription($sub, $start->toDateString(), $end->toDateString());
            $sub->next_billing_at = $end->addDay()->toDateString();
            $sub->save();
            $count++;
        }
        return $count;
    }

    /** Tandai invoice overdue kalau due_date < today dan status masih unpaid. */
    public function markOverdue(): int
    {
        return TenantInvoice::where('status', TenantInvoice::STATUS_UNPAID)
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => TenantInvoice::STATUS_OVERDUE]);
    }

    /**
     * Auto-suspend tenant yang overdue lebih dari grace_days.
     *  - Set subscription status='suspended', tenant.is_active=false
     *  - User tenant gak bisa login lagi (login flow ngecek is_active)
     */
    public function autoSuspendOverdue(): int
    {
        $count = 0;
        $subs = TenantSubscription::with(['tenant', 'plan'])
            ->whereIn('status', [TenantSubscription::STATUS_ACTIVE, TenantSubscription::STATUS_PAST_DUE])
            ->get();

        foreach ($subs as $sub) {
            $grace   = $sub->graceDays();
            $cutoff  = Carbon::today()->subDays($grace)->toDateString();
            // Cari invoice overdue paling lama, kalau melewati cutoff → suspend
            $hasOverdue = TenantInvoice::where('tenant_id', $sub->tenant_id)
                ->where('status', TenantInvoice::STATUS_OVERDUE)
                ->whereDate('due_date', '<', $cutoff)
                ->exists();
            if ($hasOverdue) {
                $sub->status       = TenantSubscription::STATUS_SUSPENDED;
                $sub->suspended_at = now()->toDateString();
                $sub->save();
                if ($sub->tenant) {
                    $sub->tenant->is_active = false;
                    $sub->tenant->save();
                }
                $count++;
            }
        }
        return $count;
    }

    /**
     * Konfirmasi payment → set status confirmed, recheck invoice fully paid.
     * Kalau invoice udah lunas → set status='paid', paid_at=now.
     */
    public function confirmPayment(TenantPayment $payment, int $confirmedBy): TenantPayment
    {
        return DB::transaction(function () use ($payment, $confirmedBy) {
            $payment->status       = TenantPayment::STATUS_CONFIRMED;
            $payment->confirmed_by = $confirmedBy;
            $payment->confirmed_at = now();
            $payment->save();

            $invoice = $payment->invoice()->first();
            if ($invoice && $invoice->isFullyPaid()) {
                $invoice->status  = TenantInvoice::STATUS_PAID;
                $invoice->paid_at = now();
                $invoice->save();

                // Kalau invoice udah paid & tenant suspended, re-activate
                $sub = $invoice->subscription;
                if ($sub && $sub->status === TenantSubscription::STATUS_SUSPENDED) {
                    // Cek apakah masih ada invoice overdue lain
                    $stillOverdue = TenantInvoice::where('tenant_id', $sub->tenant_id)
                        ->where('status', TenantInvoice::STATUS_OVERDUE)
                        ->exists();
                    if (!$stillOverdue) {
                        $sub->status       = TenantSubscription::STATUS_ACTIVE;
                        $sub->suspended_at = null;
                        $sub->save();
                        if ($sub->tenant) {
                            $sub->tenant->is_active = true;
                            $sub->tenant->save();
                        }
                    }
                }
            }
            return $payment->fresh();
        });
    }

    /**
     * Generate invoice number: SUB-YYYY-MM-XXXX (urut per bulan).
     * Thread-safe via SELECT FOR UPDATE.
     */
    protected function nextInvoiceNumber(): string
    {
        $prefix = config('ahnet.tenant_billing.invoice_prefix', 'SUB');
        $ym = now()->format('Y-m');
        $base = "{$prefix}-{$ym}-";

        return DB::transaction(function () use ($base) {
            $last = TenantInvoice::where('invoice_number', 'like', $base . '%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('invoice_number');
            $seq = 1;
            if ($last && preg_match('/-(\d+)$/', $last, $m)) {
                $seq = (int) $m[1] + 1;
            }
            return $base . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        });
    }
}
