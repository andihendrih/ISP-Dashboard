<?php

namespace App\Http\Controllers;

use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\TenantSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Tenant-side billing view: tenant admin lihat subscription + invoice mereka sendiri.
 * Customer role di-block — billing ini cuma untuk tenant staff.
 */
class MySubscriptionController extends Controller
{
    public function index(): View
    {
        $u = Auth::user();
        abort_if(!$u || !$u->tenant_id, 403, 'Tenant tidak terdeteksi.');
        abort_if($u->role?->name === 'customer', 403, 'Customer tidak punya akses billing.');

        $sub = TenantSubscription::with('plan')
            ->where('tenant_id', $u->tenant_id)
            ->orderByDesc('id')
            ->first();

        $invoices = TenantInvoice::where('tenant_id', $u->tenant_id)
            ->orderByDesc('id')
            ->paginate(20);

        return view('billing.my-subscription.index', compact('sub', 'invoices'));
    }

    public function invoiceShow(TenantInvoice $invoice): View
    {
        $u = Auth::user();
        abort_if(!$u || $invoice->tenant_id !== $u->tenant_id, 403);
        $invoice->load(['plan', 'payments']);
        return view('billing.my-subscription.invoice', compact('invoice'));
    }

    public function submitPayment(TenantInvoice $invoice, Request $request): RedirectResponse
    {
        $u = Auth::user();
        abort_if(!$u || $invoice->tenant_id !== $u->tenant_id, 403);
        abort_if($invoice->status === TenantInvoice::STATUS_PAID, 400, 'Invoice sudah lunas.');
        abort_if($invoice->status === TenantInvoice::STATUS_CANCELLED, 400, 'Invoice sudah dibatalkan.');

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
        return redirect()->route('my_subscription.invoices.show', $invoice)
            ->with('success', 'Pembayaran kamu dicatat. Menunggu konfirmasi superadmin.');
    }
}
