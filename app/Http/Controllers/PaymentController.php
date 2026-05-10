<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private BillingService $billing) {}

    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['invoice.customer', 'recorder'])
            ->latest('paid_at')
            ->paginate(25);

        return view('payments.index', compact('payments'));
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === Invoice::STATUS_CANCELLED) {
            return back()->with('error', 'Invoice ini sudah dibatalkan.');
        }
        if ($invoice->status === Invoice::STATUS_LUNAS) {
            return back()->with('warning', 'Invoice ini sudah lunas.');
        }

        $data = $request->validate([
            'amount'    => 'required|numeric|min:0.01',
            'method'    => 'required|in:cash,transfer,qris,va,other',
            'reference' => 'nullable|string|max:120',
            'paid_at'   => 'nullable|date',
            'notes'     => 'nullable|string',
        ]);
        $data['paid_at']     = $data['paid_at'] ?? now();
        $data['recorded_by'] = $request->user()?->id;

        $this->billing->recordPayment($invoice, $data);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Pembayaran dicatat.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $invoice = $payment->invoice;
        $payment->delete();

        if ($invoice) {
            $totalPaid = (float) $invoice->payments()->sum('amount');
            $update = ['paid_amount' => $totalPaid];
            if ($totalPaid + 0.0001 < (float) $invoice->total_amount && $invoice->status === Invoice::STATUS_LUNAS) {
                // Revert to belum_lunas / terlambat depending on due_date
                $update['status']  = $invoice->due_date && $invoice->due_date < now()->toDateString()
                    ? Invoice::STATUS_TERLAMBAT
                    : Invoice::STATUS_BELUM_LUNAS;
                $update['paid_at'] = null;
            }
            $invoice->update($update);
        }

        return back()->with('success', 'Pembayaran dihapus.');
    }
}
