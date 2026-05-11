<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\TenantPlan;
use App\Models\TenantSubscription;
use App\Services\TenantBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Admin billing — superadmin manage subscription + invoice + payment tenant.
 * Semua method auto-protected via middleware role:superadmin di routes/web.php.
 */
class TenantBillingController extends Controller
{
    public function __construct(private TenantBillingService $svc) {}

    /** Dashboard ringkasan MRR + overdue tenants + recent invoices. */
    public function index(): View
    {
        $totalMrr = (int) TenantSubscription::query()
            ->whereIn('status', [TenantSubscription::STATUS_ACTIVE, TenantSubscription::STATUS_PAST_DUE])
            ->join('tenant_plans', 'tenant_subscriptions.plan_id', '=', 'tenant_plans.id')
            ->sum(\DB::raw('COALESCE(tenant_subscriptions.price_override, tenant_plans.price)'));

        $activeCount      = TenantSubscription::where('status', TenantSubscription::STATUS_ACTIVE)->count();
        $suspendedCount   = TenantSubscription::where('status', TenantSubscription::STATUS_SUSPENDED)->count();
        $overdueInvoices  = TenantInvoice::where('status', TenantInvoice::STATUS_OVERDUE)->count();
        $unpaidInvoices   = TenantInvoice::where('status', TenantInvoice::STATUS_UNPAID)->count();

        $recentInvoices = TenantInvoice::with(['tenant', 'plan'])
            ->orderByDesc('id')->limit(10)->get();

        $overdueTenantsList = TenantInvoice::with('tenant')
            ->where('status', TenantInvoice::STATUS_OVERDUE)
            ->orderBy('due_date')
            ->limit(20)->get();

        return view('admin.tenant-billing.index', compact(
            'totalMrr', 'activeCount', 'suspendedCount',
            'overdueInvoices', 'unpaidInvoices',
            'recentInvoices', 'overdueTenantsList'
        ));
    }

    /* ============================ SUBSCRIPTIONS ============================ */

    public function subscriptions(): View
    {
        $subs = TenantSubscription::with(['tenant', 'plan'])
            ->orderByDesc('id')->paginate(30);
        $plans   = TenantPlan::where('is_active', true)->orderBy('sort_order')->get();
        $tenants = Tenant::orderBy('name')->get();
        return view('admin.tenant-billing.subscriptions.index', compact('subs', 'plans', 'tenants'));
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id'           => ['required', 'integer', 'exists:tenants,id'],
            'plan_id'             => ['required', 'integer', 'exists:tenant_plans,id'],
            'price_override'      => ['nullable', 'integer', 'min:0'],
            'grace_days_override' => ['nullable', 'integer', 'min:0', 'max:60'],
        ]);
        $tenant = Tenant::findOrFail($data['tenant_id']);
        $plan   = TenantPlan::findOrFail($data['plan_id']);
        $sub = $this->svc->subscribe(
            $tenant, $plan,
            $data['price_override'] ?? null,
            $data['grace_days_override'] ?? null,
        );
        return redirect()->route('tenant_billing.subscriptions.index')
            ->with('success', "Tenant {$tenant->name} subscribed ke plan {$plan->name}. Invoice pertama: " . number_format($sub->invoices()->latest()->value('amount') ?? 0, 0, ',', '.'));
    }

    public function cancelSubscription(TenantSubscription $sub): RedirectResponse
    {
        $sub->status       = TenantSubscription::STATUS_CANCELLED;
        $sub->cancelled_at = now()->toDateString();
        $sub->save();
        return back()->with('success', "Subscription tenant {$sub->tenant?->name} di-cancel. Tenant tetep aktif sampai period berakhir.");
    }

    /* ============================ INVOICES ============================ */

    public function invoicesIndex(Request $request): View
    {
        $status = $request->query('status');
        $tenant = $request->query('tenant_id');
        $q = TenantInvoice::with(['tenant', 'plan'])->orderByDesc('id');
        if ($status) $q->where('status', $status);
        if ($tenant) $q->where('tenant_id', $tenant);
        $invoices = $q->paginate(30)->withQueryString();
        $tenants  = Tenant::orderBy('name')->get();
        return view('admin.tenant-billing.invoices.index', compact('invoices', 'tenants', 'status', 'tenant'));
    }

    public function invoiceShow(TenantInvoice $invoice): View
    {
        $invoice->load(['tenant', 'plan', 'subscription', 'payments.confirmer']);
        return view('admin.tenant-billing.invoices.show', compact('invoice'));
    }

    public function cancelInvoice(TenantInvoice $invoice): RedirectResponse
    {
        if ($invoice->status === TenantInvoice::STATUS_PAID) {
            return back()->with('error', 'Invoice lunas gak bisa dibatalin.');
        }
        $invoice->status = TenantInvoice::STATUS_CANCELLED;
        $invoice->save();
        return back()->with('success', "Invoice {$invoice->invoice_number} di-cancel.");
    }

    public function markPaid(TenantInvoice $invoice, Request $request): RedirectResponse
    {
        $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        // Insert dummy payment confirmed = invoice.amount supaya consistent
        $payment = TenantPayment::create([
            'invoice_id'     => $invoice->id,
            'tenant_id'      => $invoice->tenant_id,
            'amount'         => $invoice->remainingAmount(),
            'method'         => TenantPayment::METHOD_BANK,
            'status'         => TenantPayment::STATUS_PENDING,
            'transferred_at' => now()->toDateString(),
            'note'           => $request->input('note', 'Marked paid manually by superadmin'),
        ]);
        $this->svc->confirmPayment($payment, Auth::id());
        return back()->with('success', "Invoice {$invoice->invoice_number} ditandai lunas.");
    }

    /* ============================ PAYMENTS ============================ */

    public function storePayment(TenantInvoice $invoice, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount'         => ['required', 'integer', 'min:1'],
            'method'         => ['required', 'in:bank_transfer,va,ewallet,cash,other'],
            'reference'      => ['nullable', 'string', 'max:120'],
            'transferred_at' => ['required', 'date'],
            'note'           => ['nullable', 'string', 'max:500'],
            'proof'          => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);
        if ($request->hasFile('proof')) {
            $data['proof_path'] = $request->file('proof')->store('tenant-payments', 'public');
        }
        $data['invoice_id'] = $invoice->id;
        $data['tenant_id']  = $invoice->tenant_id;
        $data['status']     = TenantPayment::STATUS_PENDING;
        TenantPayment::create($data);
        return back()->with('success', 'Payment dicatat, menunggu konfirmasi.');
    }

    public function confirmPayment(TenantPayment $payment): RedirectResponse
    {
        $this->svc->confirmPayment($payment, Auth::id());
        return back()->with('success', 'Payment dikonfirmasi.');
    }

    public function rejectPayment(TenantPayment $payment, Request $request): RedirectResponse
    {
        $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $payment->status = TenantPayment::STATUS_REJECTED;
        $payment->note   = trim(($payment->note ? $payment->note . "\n" : '') . 'Rejected: ' . $request->input('note', ''));
        $payment->save();
        return back()->with('success', 'Payment ditolak.');
    }
}
