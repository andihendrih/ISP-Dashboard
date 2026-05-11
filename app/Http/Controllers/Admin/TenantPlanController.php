<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TenantPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantPlanController extends Controller
{
    public function index(): View
    {
        $plans = TenantPlan::orderBy('sort_order')->orderBy('price')->get();
        return view('admin.tenant-billing.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('admin.tenant-billing.plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['features'] = $this->parseFeatures($request->input('features_json'));
        TenantPlan::create($data);
        return redirect()->route('tenant_billing.plans.index')->with('success', 'Plan dibuat.');
    }

    public function edit(TenantPlan $plan): View
    {
        return view('admin.tenant-billing.plans.edit', compact('plan'));
    }

    public function update(Request $request, TenantPlan $plan): RedirectResponse
    {
        $data = $this->validateData($request, $plan->id);
        $data['features'] = $this->parseFeatures($request->input('features_json'));
        $plan->update($data);
        return redirect()->route('tenant_billing.plans.index')->with('success', 'Plan di-update.');
    }

    public function destroy(TenantPlan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Plan ini masih dipakai oleh subscription, tidak bisa dihapus.');
        }
        $plan->delete();
        return back()->with('success', 'Plan dihapus.');
    }

    protected function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code'          => ['required', 'string', 'max:32', 'alpha_dash', 'unique:tenant_plans,code' . ($ignoreId ? ',' . $ignoreId : '')],
            'name'          => ['required', 'string', 'max:80'],
            'price'         => ['required', 'integer', 'min:0'],
            'max_customers' => ['nullable', 'integer', 'min:0'],
            'max_devices'   => ['nullable', 'integer', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
        ]);
    }

    protected function parseFeatures(?string $json): ?array
    {
        if (!$json) return null;
        $parsed = json_decode($json, true);
        return is_array($parsed) ? $parsed : null;
    }
}
