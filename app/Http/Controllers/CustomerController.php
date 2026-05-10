<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use App\Models\DeviceMikrotik;
use App\Models\ServicePlan;
use App\Services\CustomerAccountService;
use App\Services\RadiusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        private RadiusService $radius,
        private CustomerAccountService $accounts,
    ) {}

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
            'radiusGroups'=> $this->safeRadiusGroups(),
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
            'radius_password'   => ['nullable', 'string', 'max:120'],
            'auto_radius'       => ['sometimes', 'boolean'],
            'mikrotik_device_id'=> ['nullable', 'integer', 'exists:devices_mikrotik,id'],
            'joined_at'         => ['nullable', 'date'],
            'expired_at'        => ['nullable', 'date'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ]);

        $autoRadius = (bool) ($data['auto_radius'] ?? true);
        unset($data['auto_radius']);

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

        // Auto-generate username + password kalau auto_radius dicentang
        $radiusFlash = null;
        if ($autoRadius && $data['service_type'] === 'pppoe') {
            if (empty($data['radius_username'])) {
                $data['radius_username'] = $this->radius->generatePppoeUsername($data['full_name']);
            }
            if (empty($data['radius_password'])) {
                $data['radius_password'] = $this->radius->generatePppoePassword();
            }

            try {
                $this->radius->createPppoeUser(
                    $data['radius_username'],
                    $data['radius_password'],
                    $data['package'] ?? null,
                    $data['rate_limit'] ?? null
                );
                $radiusFlash = "Username: {$data['radius_username']} | Password: {$data['radius_password']}";
            } catch (\Throwable $e) {
                Log::warning('RADIUS provisioning failed for new customer: '.$e->getMessage());
                $radiusFlash = "RADIUS provisioning gagal: {$e->getMessage()} (data pelanggan tetap tersimpan, lo bisa retry dari halaman edit).";
            }
        }

        $row = CustomerProfile::create($data);

        // Auto-create portal user account (role=customer) untuk client portal nanti.
        $accountFlash = null;
        try {
            $acct = $this->accounts->ensureForCustomer($row);
            if ($acct['plain_password']) {
                $accountFlash = "Akun portal — Email: {$acct['user']->email} | Password: {$acct['plain_password']}";
            }
        } catch (\Throwable $e) {
            Log::warning('Auto-create customer account failed: '.$e->getMessage());
            $accountFlash = "Akun portal gagal dibuat: {$e->getMessage()} (data pelanggan tetap tersimpan).";
        }

        $msg = "Pelanggan {$row->customer_code} — {$row->full_name} berhasil ditambahkan.";
        if ($radiusFlash) {
            $msg .= " {$radiusFlash}";
        }
        if ($accountFlash) {
            $msg .= " {$accountFlash}";
        }

        return redirect()
            ->route('customers.index')
            ->with('success', $msg);
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
            'row'         => $row,
            'devices'     => DeviceMikrotik::where('is_active', true)->get(),
            'plans'       => ServicePlan::where('is_active', true)->orderBy('name')->get(),
            'radiusGroups'=> $this->safeRadiusGroups(),
        ]);
    }

    /**
     * Hard delete pelanggan + propagate hapus ke FreeRADIUS
     * (radcheck/radreply/radusergroup/radacct/radpostauth) supaya
     * data di RADIUS tidak menumpuk.
     */
    public function destroy(int $id): RedirectResponse
    {
        $row = CustomerProfile::findOrFail($id);
        $username = $row->radius_username;

        if ($username) {
            try {
                $this->radius->deleteUser($username);
            } catch (\Throwable $e) {
                Log::warning("RADIUS hard-delete failed for {$username}: ".$e->getMessage());
                return back()->with('error', "Gagal hapus user RADIUS '{$username}': {$e->getMessage()}");
            }
        }

        $row->delete();
        return redirect()->route('customers.index')
            ->with('success', "Pelanggan {$row->customer_code} dihapus" . ($username ? " (RADIUS user '{$username}' juga dihapus)." : '.'));
    }

    /** Safe wrapper: kalau RADIUS DB belum konfig, kembalikan array kosong tanpa crash. */
    protected function safeRadiusGroups(): array
    {
        try {
            return $this->radius->categorizeGroups();
        } catch (\Throwable $e) {
            return ['home' => [], 'broadband' => [], 'bisnis' => [], 'hotspot' => [], 'other' => []];
        }
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
            'radius_username'   => ['nullable', 'string', 'max:120'],
            'radius_password'   => ['nullable', 'string', 'max:120'],
            'mikrotik_device_id'=> ['nullable', 'integer', 'exists:devices_mikrotik,id'],
            'expired_at'        => ['nullable', 'date'],
        ]);
        $data['billing_enabled'] = (bool) ($data['billing_enabled'] ?? false);

        $oldUsername = $row->radius_username;
        $oldPassword = $row->radius_password;
        $row->update($data);

        // Provision / sync ke RADIUS
        if ($row->radius_username) {
            try {
                // Kalau user belum ada di radcheck → create; kalau sudah → update password & rate
                $exists = \App\Models\Radius\Radcheck::where('username', $row->radius_username)
                    ->where('attribute', 'Cleartext-Password')
                    ->exists();

                if (!$exists) {
                    $this->radius->createPppoeUser(
                        $row->radius_username,
                        $row->radius_password ?: $this->radius->generatePppoePassword(),
                        $row->package ?: null,
                        $row->rate_limit ?: null
                    );
                } else {
                    if ($row->radius_password && $row->radius_password !== $oldPassword) {
                        $this->radius->setPassword($row->radius_username, $row->radius_password);
                    }
                    $this->radius->setRateLimit($row->radius_username, $row->rate_limit ?: null);
                    if (!empty($row->package)) {
                        $this->radius->setGroup($row->radius_username, $row->package);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('RADIUS sync on customer update failed: '.$e->getMessage());
            }
        }

        return redirect()->route('customers.index')->with('success', 'Pelanggan diperbarui.');
    }

    /**
     * Regenerate RADIUS password for a customer (one-click reset).
     * Pakai generator alphanumeric-only supaya aman lewat semua jalur auth.
     */
    public function regenerateRadiusPassword(int $id): RedirectResponse
    {
        $row = CustomerProfile::findOrFail($id);

        if (!$row->radius_username) {
            return back()->with('error', "Pelanggan {$row->customer_code} belum punya RADIUS username — tidak bisa regenerate password.");
        }

        $newPassword = $this->radius->generatePppoePassword();

        try {
            $this->radius->setPassword($row->radius_username, $newPassword);
            $row->update(['radius_password' => $newPassword]);
            return back()->with('success',
                "Password RADIUS {$row->radius_username} berhasil di-regenerate. " .
                "Username: {$row->radius_username} | Password baru: {$newPassword}"
            );
        } catch (\Throwable $e) {
            Log::warning("RADIUS regenerate password failed for {$row->radius_username}: ".$e->getMessage());
            return back()->with('error', "Regenerate password gagal: {$e->getMessage()}");
        }
    }
}
