<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengaturan → Tenants (superadmin only).
 *
 * Hanya superadmin yang bisa CRUD tenant. Staff biasa nggak akan pernah
 * lihat menu ini (route-level guard via `role:superadmin`).
 *
 * Pas tenant baru dibikin, controller juga generate user admin awal supaya
 * tenant yg baru itu bisa langsung login. Password awal di-flash sekali.
 */
class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $q = Tenant::query()->orderBy('name');

        if ($s = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('contact_email', 'like', "%{$s}%");
            });
        }

        // Counters per tenant (customers, users) — supaya superadmin bisa
        // langsung lihat skala tiap tenant.
        $rows = $q->paginate(25)->withQueryString();
        $rows->getCollection()->transform(function ($t) {
            $t->customers_count = DB::table('customer_profiles')
                ->where('tenant_id', $t->id)->count();
            $t->users_count = DB::table('users')
                ->where('tenant_id', $t->id)->count();
            return $t;
        });

        return view('settings.tenants.index', [
            'rows' => $rows,
            'qs'   => $s,
            'stats' => [
                'total'    => Tenant::count(),
                'active'   => Tenant::where('is_active', true)->count(),
                'inactive' => Tenant::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('settings.tenants.create', [
            'plans' => Tenant::PLANS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'           => ['required', 'string', 'min:3', 'max:16', 'alpha_dash', 'unique:tenants,code'],
            'name'           => ['required', 'string', 'max:120'],
            'plan'           => ['required', Rule::in(Tenant::PLANS)],
            'max_customers'  => ['nullable', 'integer', 'min:1'],
            'is_active'      => ['sometimes', 'boolean'],
            // Brand
            'brand_company'  => ['nullable', 'string', 'max:120'],
            'brand_phone'    => ['nullable', 'string', 'max:32'],
            'brand_email'    => ['nullable', 'email', 'max:120'],
            'brand_address'  => ['nullable', 'string', 'max:255'],
            // Contact PIC
            'contact_name'   => ['nullable', 'string', 'max:120'],
            'contact_phone'  => ['nullable', 'string', 'max:32'],
            'contact_email'  => ['nullable', 'email', 'max:120'],
            'notes'          => ['nullable', 'string', 'max:2000'],
            // Initial admin user
            'admin_name'     => ['required', 'string', 'max:120'],
            'admin_email'    => ['required', 'email', 'max:120', 'unique:users,email'],
            'admin_password' => ['nullable', 'string', 'min:6', 'max:64'],
        ]);

        $code = strtolower($data['code']);
        $slug = $code;

        // Bungkus dalam transaction supaya gagal di tengah gak ninggalin
        // tenant tanpa admin atau admin yatim.
        [$tenant, $admin, $plain] = DB::transaction(function () use ($data, $code, $slug) {
            $tenant = Tenant::create([
                'code'          => $code,
                'slug'          => $slug,
                'name'          => $data['name'],
                'plan'          => $data['plan'],
                'max_customers' => $data['max_customers'] ?? null,
                'is_active'     => (bool) ($data['is_active'] ?? true),
                'brand_company' => $data['brand_company'] ?? null,
                'brand_phone'   => $data['brand_phone'] ?? null,
                'brand_email'   => $data['brand_email'] ?? null,
                'brand_address' => $data['brand_address'] ?? null,
                'contact_name'  => $data['contact_name'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'notes'         => $data['notes'] ?? null,
            ]);

            $adminRole = Role::where('name', Role::ADMIN)->first();
            $plain = !empty($data['admin_password'])
                ? $data['admin_password']
                : Str::password(10, true, true, false, false);

            $admin = User::create([
                'name'      => $data['admin_name'],
                'email'     => $data['admin_email'],
                'password'  => Hash::make($plain),
                'role_id'   => $adminRole?->id,
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]);

            return [$tenant, $admin, $plain];
        });

        // Optional: kirim credential via WhatsApp ke PIC tenant.
        $waInfo = '';
        if ($request->boolean('send_wa') && !empty($tenant->contact_phone)) {
            $waInfo = $this->dispatchAdminCredentialWa($tenant, $admin, $plain);
        }

        return redirect()->route('settings.tenants.index')->with(
            'success',
            "Tenant {$tenant->name} ({$tenant->code}) dibuat. " .
            "Admin awal — Email: {$admin->email} | Password: {$plain}" . $waInfo
        );
    }

    /**
     * Regenerate password admin tenant. Cuma superadmin yang boleh.
     * Target = user admin pertama di tenant ini (ordered by id).
     * Optional kirim password baru via WhatsApp ke PIC.
     */
    public function regenerateAdminPassword(Request $request, Tenant $tenant): RedirectResponse
    {
        $admin = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereHas('role', fn ($w) => $w->where('name', Role::ADMIN))
            ->orderBy('id')
            ->first();

        if (!$admin) {
            return back()->with('error',
                "Tenant {$tenant->name} belum punya user admin. Tambah admin manual lewat menu Pengguna."
            );
        }

        $plain = Str::password(10, true, true, false, false);
        $admin->update(['password' => Hash::make($plain), 'is_active' => true]);

        $waInfo = '';
        if ($request->boolean('send_wa') && !empty($tenant->contact_phone)) {
            $waInfo = $this->dispatchAdminCredentialWa($tenant, $admin, $plain);
        }

        return back()->with('success',
            "Password admin tenant {$tenant->name} di-regenerate. " .
            "Email: {$admin->email} | Password baru: {$plain}" . $waInfo
        );
    }

    /**
     * Kirim credential admin tenant via WhatsApp ke PIC contact_phone.
     * Returns suffix string buat di-append ke flash success.
     */
    protected function dispatchAdminCredentialWa(Tenant $tenant, User $admin, string $plain): string
    {
        $loginUrl = url('/login');
        $body = "Halo {$tenant->contact_name},\n\n"
              . "Akun admin portal {$tenant->name} sudah siap:\n\n"
              . "• URL: {$loginUrl}\n"
              . "• Email: {$admin->email}\n"
              . "• Password: {$plain}\n\n"
              . "Silakan login lalu ganti password Anda di menu Pengaturan. "
              . "Pesan ini berisi credential — jangan diteruskan.";
        try {
            app(NotificationService::class)->sendTestWa($tenant->contact_phone, $body);
            return " — dikirim via WA ke {$tenant->contact_phone}.";
        } catch (\Throwable $e) {
            Log::warning('Tenant credential WA failed: ' . $e->getMessage(), [
                'tenant_id' => $tenant->id, 'phone' => $tenant->contact_phone,
            ]);
            return " (Gagal kirim WA: " . $e->getMessage() . ")";
        }
    }

    public function edit(Tenant $tenant): View
    {
        return view('settings.tenants.edit', [
            'tenant' => $tenant,
            'plans'  => Tenant::PLANS,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'code'          => ['required', 'string', 'min:3', 'max:16', 'alpha_dash', Rule::unique('tenants', 'code')->ignore($tenant->id)],
            'name'          => ['required', 'string', 'max:120'],
            'plan'          => ['required', Rule::in(Tenant::PLANS)],
            'max_customers' => ['nullable', 'integer', 'min:1'],
            'is_active'     => ['sometimes', 'boolean'],
            'brand_company' => ['nullable', 'string', 'max:120'],
            'brand_phone'   => ['nullable', 'string', 'max:32'],
            'brand_email'   => ['nullable', 'email', 'max:120'],
            'brand_address' => ['nullable', 'string', 'max:255'],
            'contact_name'  => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'notes'         => ['nullable', 'string', 'max:2000'],
        ]);
        $data['code'] = strtolower($data['code']);
        $data['slug'] = $data['code'];
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $tenant->update($data);
        return redirect()->route('settings.tenants.index')->with('success', "Tenant {$tenant->name} diperbarui.");
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        // Tenant default (ahnet, id=1) tidak boleh dihapus — itu container
        // platform owner sendiri.
        if ($tenant->code === Tenant::DEFAULT_CODE) {
            return back()->with('error', "Tenant default '{$tenant->code}' tidak boleh dihapus.");
        }

        // Tolak hapus kalau masih ada customer / invoice / dll.
        $hasData = DB::table('customer_profiles')->where('tenant_id', $tenant->id)->exists()
            || DB::table('invoices')->where('tenant_id', $tenant->id)->exists()
            || DB::table('users')->where('tenant_id', $tenant->id)->where('id', '!=', auth()->id())->exists();
        if ($hasData) {
            return back()->with('error',
                "Tenant {$tenant->name} masih punya data (customer/invoice/user). " .
                "Hapus / migrate data tersebut dulu sebelum delete tenant."
            );
        }

        $name = $tenant->name;
        $tenant->delete();
        return redirect()->route('settings.tenants.index')
            ->with('success', "Tenant {$name} dihapus.");
    }

    /**
     * Switch active tenant (superadmin only). Set session "active_tenant_id"
     * supaya middleware ResolveTenant scope query ke tenant tersebut. Hasilnya:
     * superadmin "view as" tenant — semua menu nampilin data tenant itu doang.
     */
    public function switch(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->session()->put('active_tenant_id', $tenant->id);
        return back()->with('success', "Sekarang lo lihat sebagai tenant: {$tenant->name}. Klik 'Lihat Semua Tenant' di header untuk reset.");
    }

    public function clearSwitch(Request $request): RedirectResponse
    {
        $request->session()->forget('active_tenant_id');
        return back()->with('success', 'Mode lihat semua tenant aktif (superadmin global).');
    }
}
