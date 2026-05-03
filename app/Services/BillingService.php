<?php

namespace App\Services;

use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServicePlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BillingService
{
    /**
     * Generate invoices for all active billing-enabled customers for the given period.
     * Returns ['created' => int, 'skipped' => int, 'errors' => int].
     */
    public function generateMonthlyInvoices(int $year, int $month, ?int $dueDay = null): array
    {
        $created  = 0;
        $skipped  = 0;
        $errors   = 0;

        $dueDay = $dueDay ?? (int) config('ahnet.billing.due_day', 7);

        $customers = CustomerProfile::query()
            ->whereNotNull('service_plan_id')
            ->where('billing_enabled', true)
            ->whereIn('status', ['active', 'isolir'])
            ->with('servicePlan')
            ->get();

        foreach ($customers as $customer) {
            try {
                $invoice = $this->createInvoiceForPeriod($customer, $year, $month, $dueDay);
                if ($invoice === null) {
                    $skipped++;
                } else {
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors++;
                logger()->error('billing.generate_failed', [
                    'customer_id' => $customer->id,
                    'period'      => "{$year}-{$month}",
                    'message'     => $e->getMessage(),
                ]);
            }
        }

        return compact('created', 'skipped', 'errors');
    }

    /**
     * Create a single invoice for the given customer + period if one doesn't exist yet.
     * Applies prorate when joined_at falls within the same period.
     * Returns the new Invoice or null if skipped (already exists).
     */
    public function createInvoiceForPeriod(CustomerProfile $customer, int $year, int $month, int $dueDay = 7): ?Invoice
    {
        $existing = Invoice::query()
            ->where('customer_profile_id', $customer->id)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        if ($existing) {
            return null;
        }

        $plan = $customer->servicePlan;
        if (! $plan) {
            throw new \RuntimeException("Customer #{$customer->id} has no service plan");
        }

        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd   = $periodStart->copy()->endOfMonth()->startOfDay();
        $daysInMonth = (int) $periodStart->daysInMonth;

        // Determine prorate
        $isProrated   = false;
        $daysCharged  = $daysInMonth;
        $effectiveStart = $periodStart->copy();

        if ($customer->joined_at) {
            $joined = Carbon::parse($customer->joined_at)->startOfDay();
            if ($joined->year === $year && $joined->month === $month && $joined->day > 1) {
                $isProrated     = true;
                $effectiveStart = $joined->copy();
                $daysCharged    = $daysInMonth - $joined->day + 1;
            } elseif ($joined->greaterThan($periodEnd)) {
                // Customer hasn't joined yet — skip
                return null;
            }
        }

        $price       = (float) $plan->price;
        $taxPercent  = (float) ($plan->tax_percent ?? 0);
        $baseAmount  = $isProrated
            ? round($price * ($daysCharged / $daysInMonth), 2)
            : $price;
        $taxAmount   = round($baseAmount * ($taxPercent / 100), 2);
        $totalAmount = round($baseAmount + $taxAmount, 2);

        $dueDate = Carbon::create($year, $month, min($dueDay, $daysInMonth))->startOfDay();

        $number = $this->generateInvoiceNumber($year, $month);

        return DB::transaction(function () use (
            $customer, $plan, $year, $month, $effectiveStart, $periodEnd,
            $isProrated, $daysCharged, $daysInMonth, $baseAmount, $taxAmount,
            $totalAmount, $dueDate, $number
        ) {
            return Invoice::create([
                'invoice_number'      => $number,
                'customer_profile_id' => $customer->id,
                'service_plan_id'     => $plan->id,
                'period_year'         => $year,
                'period_month'        => $month,
                'period_start'        => $effectiveStart->toDateString(),
                'period_end'          => $periodEnd->toDateString(),
                'is_prorated'         => $isProrated,
                'days_charged'        => $daysCharged,
                'days_in_month'       => $daysInMonth,
                'base_amount'         => $baseAmount,
                'discount_amount'     => 0,
                'tax_amount'          => $taxAmount,
                'total_amount'        => $totalAmount,
                'paid_amount'         => 0,
                'due_date'            => $dueDate->toDateString(),
                'status'              => Invoice::STATUS_BELUM_LUNAS,
            ]);
        });
    }

    /**
     * Mark all unpaid invoices past their due date as TERLAMBAT.
     * Returns the number of rows affected.
     */
    public function markOverdueInvoices(?Carbon $today = null): int
    {
        $today = $today ?? Carbon::today();

        return Invoice::query()
            ->where('status', Invoice::STATUS_BELUM_LUNAS)
            ->whereDate('due_date', '<', $today)
            ->update([
                'status'     => Invoice::STATUS_TERLAMBAT,
                'updated_at' => now(),
            ]);
    }

    /**
     * Record a payment against an invoice and update its status if fully paid.
     */
    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $payment = Payment::create([
                'invoice_id'  => $invoice->id,
                'amount'      => $data['amount'],
                'method'      => $data['method'] ?? 'cash',
                'reference'   => $data['reference'] ?? null,
                'paid_at'     => $data['paid_at'] ?? now(),
                'recorded_by' => $data['recorded_by'] ?? null,
                'notes'       => $data['notes'] ?? null,
            ]);

            $totalPaid = (float) $invoice->payments()->sum('amount');
            $update = ['paid_amount' => $totalPaid];

            if ($totalPaid + 0.0001 >= (float) $invoice->total_amount) {
                $update['status']  = Invoice::STATUS_LUNAS;
                $update['paid_at'] = $payment->paid_at;
            }

            $invoice->update($update);

            return $payment;
        });
    }

    /**
     * Cancel an invoice (only if not already paid).
     */
    public function cancelInvoice(Invoice $invoice, string $reason, ?int $userId = null): bool
    {
        if ($invoice->status === Invoice::STATUS_LUNAS) {
            throw new \RuntimeException('Tidak bisa membatalkan invoice yang sudah lunas');
        }

        return $invoice->update([
            'status'           => Invoice::STATUS_CANCELLED,
            'cancelled_at'     => now(),
            'cancelled_reason' => $reason,
        ]);
    }

    /**
     * Compute a unique invoice number in the form INV-YYYYMM-XXXX (zero-padded sequential).
     */
    protected function generateInvoiceNumber(int $year, int $month): string
    {
        $prefix = sprintf('INV-%04d%02d-', $year, $month);

        $count = Invoice::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->count();

        // Loop in case of race conditions
        do {
            $count++;
            $candidate = $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        } while (Invoice::where('invoice_number', $candidate)->exists());

        return $candidate;
    }
}
