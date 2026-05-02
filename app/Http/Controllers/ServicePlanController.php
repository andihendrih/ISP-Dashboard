<?php

namespace App\Http\Controllers;

use App\Models\ServicePlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServicePlanController extends Controller
{
    public function index(): View
    {
        $plans = ServicePlan::query()->orderBy('service_type')->orderBy('price')->paginate(25);
        return view('plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'         => 'required|string|max:50|unique:service_plans,code',
            'name'         => 'required|string|max:120',
            'service_type' => 'required|in:pppoe,hotspot',
            'rate_limit'   => 'nullable|string|max:50',
            'radius_group' => 'nullable|string|max:80',
            'price'        => 'required|numeric|min:0',
            'tax_percent'  => 'nullable|numeric|min:0|max:100',
            'description'  => 'nullable|string',
            'is_active'    => 'sometimes|boolean',
        ]);
        $data['is_active']   = (bool) ($data['is_active']   ?? true);
        $data['tax_percent'] = $data['tax_percent'] ?? 0;

        ServicePlan::create($data);

        return redirect()->route('plans.index')->with('success', 'Paket layanan dibuat.');
    }

    public function edit(ServicePlan $plan): View
    {
        return view('plans.edit', compact('plan'));
    }

    public function update(Request $request, ServicePlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'code'         => 'required|string|max:50|unique:service_plans,code,'.$plan->id,
            'name'         => 'required|string|max:120',
            'service_type' => 'required|in:pppoe,hotspot',
            'rate_limit'   => 'nullable|string|max:50',
            'radius_group' => 'nullable|string|max:80',
            'price'        => 'required|numeric|min:0',
            'tax_percent'  => 'nullable|numeric|min:0|max:100',
            'description'  => 'nullable|string',
            'is_active'    => 'sometimes|boolean',
        ]);
        $data['is_active']   = (bool) ($data['is_active']   ?? false);
        $data['tax_percent'] = $data['tax_percent'] ?? 0;

        $plan->update($data);

        return redirect()->route('plans.index')->with('success', 'Paket layanan diperbarui.');
    }

    public function destroy(ServicePlan $plan): RedirectResponse
    {
        if ($plan->customers()->exists()) {
            return back()->with('error', 'Tidak bisa hapus: paket dipakai oleh pelanggan.');
        }
        $plan->delete();
        return back()->with('success', 'Paket layanan dihapus.');
    }
}
