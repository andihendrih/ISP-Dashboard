<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\ServicePlan;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(private BillingService $billing) {}

    public function index(Request $request): View
    {
        $q       = $request->string('q')->toString();
        $status  = $request->string('status')->toString();
        $period  = $request->string('period')->toString(); // YYYY-MM

        $query = Invoice::query()->with(['customer', 'servicePlan'])->latest('id');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('invoice_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c
                        ->where('full_name', 'like', "%{$q}%")
                        ->orWhere('customer_code', 'like', "%{$q}%"));
            });
        }
        if (in_array($status, [Invoice::STATUS_BELUM_LUNAS, Invoice::STATUS_LUNAS, Invoice::STATUS_TERLAMBAT, Invoice::STATUS_CANCELLED], true)) {
            $query->where('status', $status);
        }
        if ($period !== '' && preg_match('/^\d{4}-\d{2}$/', $period)) {
            [$y, $m] = explode('-', $period);
            $query->where('period_year', (int) $y)->where('period_month', (int) $m);
        }

        $invoices = $query->paginate(25)->withQueryString();

        $summary = [
            'total_outstanding' => Invoice::query()
                ->whereIn('status', [Invoice::STATUS_BELUM_LUNAS, Invoice::STATUS_TERLAMBAT])
                ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as v')
                ->value('v'),
            'count_belum_lunas' => Invoice::where('status', Invoice::STATUS_BELUM_LUNAS)->count(),
            'count_terlambat'   => Invoice::where('status', Invoice::STATUS_TERLAMBAT)->count(),
            'count_lunas_bulan' => Invoice::where('status', Invoice::STATUS_LUNAS)
                ->where('period_year', now()->year)
                ->where('period_month', now()->month)
                ->count(),
        ];

        return view('invoices.index', compact('invoices', 'summary', 'q', 'status', 'period'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['customer.servicePlan', 'servicePlan', 'payments.recorder']);
        return view('invoices.show', compact('invoice'));
    }

    public function create(): View
    {
        $customers = CustomerProfile::query()->orderBy('full_name')->get();
        $plans     = ServicePlan::query()->where('is_active', true)->orderBy('name')->get();
        return view('invoices.create', compact('customers', 'plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_profile_id' => 'required|exists:customer_profiles,id',
            'period'              => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'due_day'             => 'nullable|integer|min:1|max:31',
        ]);

        $customer = CustomerProfile::findOrFail($data['customer_profile_id']);
        if (! $customer->service_plan_id) {
            return back()->with('error', 'Pelanggan belum punya paket layanan.');
        }

        [$year, $month] = explode('-', $data['period']);
        $dueDay = $data['due_day'] ?? null;

        try {
            $invoice = $this->billing->createInvoiceForPeriod($customer, (int) $year, (int) $month, $dueDay ?? (int) config('ahnet.billing.due_day'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal: '.$e->getMessage());
        }

        if (! $invoice) {
            return back()->with('warning', 'Invoice untuk pelanggan & periode tersebut sudah ada.');
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice dibuat.');
    }

    public function generateBatch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period'  => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'due_day' => 'nullable|integer|min:1|max:31',
        ]);
        [$year, $month] = explode('-', $data['period']);

        $result = $this->billing->generateMonthlyInvoices(
            (int) $year,
            (int) $month,
            $data['due_day'] ?? null
        );

        return redirect()->route('invoices.index', ['period' => $data['period']])
            ->with('success', sprintf(
                'Generate %s: %d dibuat, %d dilewati, %d error.',
                $data['period'],
                $result['created'],
                $result['skipped'],
                $result['errors']
            ));
    }

    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->billing->cancelInvoice($invoice, $data['reason'], $request->user()?->id);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal: '.$e->getMessage());
        }

        return back()->with('success', 'Invoice dibatalkan.');
    }
}
