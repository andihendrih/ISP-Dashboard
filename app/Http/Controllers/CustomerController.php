<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use App\Models\DeviceMikrotik;
use App\Models\ServicePlan;
use App\Services\RadiusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private RadiusService $radius) {}

    public function index(Request $request): View
    {
        $q = CustomerProfile::query()->orderByDesc('created_at');
        if ($s = $request->query('q')) {
            $q->where(function ($w) use ($s) {
                $w->where('full_name', 'like', "%{$s}%")
                  ->orWhere('customer_code', 'like', "%{$s}%")
                  ->orWhere('radius_username', 'like', "%{$s}%");
            });
        }
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        return view('customers.index', [
            'rows' => $q->paginate(25)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('customers.create', [
            'devices'     => DeviceMikrotik::where('is_active', true)->get(),
            'plans'       => ServicePlan::where('is_active', true)->orderBy('name')->get(),
            'suggestCode' => $this->nextCustomerCode(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_code'     => ['nullable', 'string', 'max:32', 'unique:customer_profiles,customer_code'],
            'full_name'         => ['required', 'string', 'max:120'],
            'phone'             => ['nullable', 'string', 'max:32'],
            'email'             => ['nullable', 'email', 'max:120'],
            'id_card_number'    => ['nullable', 'string', 'max:64'],
            'address'           => ['nullable', 'string', 'max:1000'],
            'latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'package'           => ['nullable', 'string', 'max:64'],
            'service_plan_id'   => ['nullable', 'integer', 'exists:service_plans,id'],
            'billing_enabled'   => ['sometimes', 'boolean'],
            'rate_limit'        => ['nullable', 'string', 'max:64'],
            'status'            => ['required', 'in:active,isolir,free,pending,inactive'],
            'service_type'      => ['required', 'in:pppoe,hotspot'],
            'radius_username'   => ['nullable', 'string', 'max:120'],
            'mikrotik_device_id'=> ['nullable', 'integer', 'exists:devices_mikrotik,id'],
            'joined_at'         => ['nullable', 'date'],
            'expired_at'        => ['nullable', 'date'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ]);

        $data['customer_code']   = ($data['customer_code'] ?? '') !== '' ? $data['customer_code'] : $this->nextCustomerCode();
        $data['billing_enabled'] = (bool) ($data['billing_enabled'] ?? false);
        $data['joined_at']       = $data['joined_at'] ?? now()->toDateString();

        // Auto-fill rate_limit dari plan kalau belum diisi
        if (empty($data['rate_limit']) && !empty($data['service_plan_id'])) {
            $plan = ServicePlan::find($data['service_plan_id']);
            if ($plan?->rate_limit) {
                $data['rate_limit'] = $plan->rate_limit;
            }
            if ($plan?->radius_group && empty($data['package'])) {
                $data['package'] = $plan->radius_group;
            }
        }

        $row = CustomerProfile::create($data);

        return redirect()
            ->route('customers.index')
            ->with('success', "Pelanggan {$row->customer_code} — {$row->full_name} berhasil ditambahkan.");
    }

    /**
     * Generate next customer_code in format AHNET-NNNN (zero-padded sequential).
     */
    protected function nextCustomerCode(): string
    {
        $prefix = 'AHNET-';
        $last = CustomerProfile::query()
            ->where('customer_code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('customer_code');

        $n = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $n = ((int) $m[1]) + 1;
        }
        do {
            $candidate = $prefix.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while (CustomerProfile::where('customer_code', $candidate)->exists());

        return $candidate;
    }

    public function edit(int $id): View
    {
        $row = CustomerProfile::findOrFail($id);
        return view('customers.edit', [
            'row'     => $row,
            'devices' => DeviceMikrotik::where('is_active', true)->get(),
            'plans'   => ServicePlan::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $row = CustomerProfile::findOrFail($id);
        $data = $request->validate([
            'full_name'         => ['required', 'string', 'max:120'],
            'phone'             => ['nullable', 'string', 'max:32'],
            'email'             => ['nullable', 'email', 'max:120'],
            'address'           => ['nullable', 'string', 'max:1000'],
            'package'           => ['nullable', 'string', 'max:64'],
            'service_plan_id'   => ['nullable', 'integer', 'exists:service_plans,id'],
            'billing_enabled'   => ['sometimes', 'boolean'],
            'rate_limit'        => ['nullable', 'string', 'max:64'],
            'status'            => ['required', 'in:active,isolir,free,pending,inactive'],
            'service_type'      => ['required', 'in:pppoe,hotspot'],
            'mikrotik_device_id'=> ['nullable', 'integer', 'exists:devices_mikrotik,id'],
            'expired_at'        => ['nullable', 'date'],
        ]);
        $data['billing_enabled'] = (bool) ($data['billing_enabled'] ?? false);
        $row->update($data);

        // Mirror radius rate limit if username known
        if ($row->radius_username) {
            try {
                $this->radius->setRateLimit($row->radius_username, $data['rate_limit'] ?? null);
                if (!empty($data['package'])) {
                    $this->radius->setGroup($row->radius_username, $data['package']);
                }
            } catch (\Throwable) { /* radius may be unreachable */ }
        }

        return redirect()->route('customers.index')->with('success', 'Pelanggan diperbarui.');
    }
}
