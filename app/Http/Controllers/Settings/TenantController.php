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
use Illuminate\Support\Facades\Schema;
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
                "Hapus / migrate data tersebut dulu sebelum delete tenant, atau pakai 'Hapus Tenant' → mode Cascade."
            );
        }

        $name = $tenant->name;
        $tenant->delete();
        return redirect()->route('settings.tenants.index')
            ->with('success', "Tenant {$name} dihapus.");
    }

    /**
     * Halaman terpisah "Hapus Tenant" (link dari header list).
     * Superadmin pilih tenant dari dropdown, system tampilin breakdown
     * data yg bakal ke-affect, dan harus type-to-confirm code tenant
     * sebelum bisa eksekusi delete.
     */
    public function deleteForm(Request $request): View
    {
        $tenants = Tenant::where('code', '!=', Tenant::DEFAULT_CODE)
            ->orderBy('name')
            ->get();

        $selected = null;
        $stats = null;
        if ($id = $request->query('tenant_id')) {
            $selected = Tenant::find($id);
            if ($selected && $selected->code !== Tenant::DEFAULT_CODE) {
                $stats = $this->tenantDataStats($selected);
            } else {
                $selected = null;
            }
        }

        return view('settings.tenants.delete', [
            'tenants'  => $tenants,
            'selected' => $selected,
            'stats'    => $stats,
        ]);
    }

    /**
     * Eksekusi delete dari halaman dedicated. Dua mode:
     *   - mode=safe: cuma boleh delete tenant kosong (sama dengan destroy()).
     *   - mode=cascade: hapus tenant + SEMUA data terkait (customers,
     *     invoices, users, devices, vouchers, tickets, dll). Wajib
     *     type-to-confirm code tenant.
     */
    public function deleteExecute(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id'    => ['required', 'integer', 'exists:tenants,id'],
            'mode'         => ['required', Rule::in(['safe', 'cascade'])],
            'confirm_code' => ['required', 'string'],
        ]);

        $tenant = Tenant::findOrFail($data['tenant_id']);

        if ($tenant->code === Tenant::DEFAULT_CODE) {
            return back()->with('error', "Tenant default '{$tenant->code}' tidak boleh dihapus.");
        }

        // Type-to-confirm: lo harus ngetik code tenant persis biar gak
        // kepencet hapus tenant yg salah.
        if (strcasecmp(trim($data['confirm_code']), $tenant->code) !== 0) {
            return back()
                ->withInput()
                ->with('error', "Konfirmasi gagal: ketik code tenant persis '{$tenant->code}' untuk konfirmasi.");
        }

        $name = $tenant->name;
        $code = $tenant->code;

        if ($data['mode'] === 'safe') {
            $hasData = DB::table('customer_profiles')->where('tenant_id', $tenant->id)->exists()
                || DB::table('invoices')->where('tenant_id', $tenant->id)->exists()
                || DB::table('users')->where('tenant_id', $tenant->id)->where('id', '!=', auth()->id())->exists();
            if ($hasData) {
                return back()->withInput()->with('error',
                    "Tenant {$tenant->name} masih punya data. Pakai mode Cascade kalau lo memang mau hapus semuanya, atau migrate data dulu."
                );
            }
            $tenant->delete();
            return redirect()->route('settings.tenants.index')
                ->with('success', "Tenant {$name} ({$code}) dihapus.");
        }

        // mode=cascade: hapus semua data tenant.
        $deleted = $this->cascadeDeleteTenantData($tenant);
        $tenant->delete();

        $summary = collect($deleted)
            ->map(fn ($cnt, $tbl) => "{$tbl}={$cnt}")
            ->implode(', ');

        return redirect()->route('settings.tenants.index')
            ->with('success', "Tenant {$name} ({$code}) dan semua datanya dihapus. Total: {$summary}.");
    }

    /**
     * Hitung data yang ke-affect kalau tenant di-cascade-delete.
     * Dipakai di halaman konfirmasi untuk transparansi ke superadmin.
     */
    protected function tenantDataStats(Tenant $tenant): array
    {
        $tables = $this->cascadeTables();
        $stats = [];
        foreach ($tables as $tbl) {
            $stats[$tbl] = DB::table($tbl)->where('tenant_id', $tenant->id)->count();
        }
        // RADIUS rows yang ke-link ke customer tenant ini (radcheck/radreply
        // tidak punya tenant_id, jadi kita match by username).
        $custUsernames = DB::table('customer_profiles')
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('radius_username')
            ->pluck('radius_username')
            ->all();
        if (!empty($custUsernames)) {
            foreach (['radcheck', 'radreply', 'radusergroup'] as $rt) {
                if (Schema::hasTable($rt)) {
                    $stats[$rt] = DB::table($rt)->whereIn('username', $custUsernames)->count();
                } else {
                    $stats[$rt] = 0;
                }
            }
        } else {
            $stats['radcheck'] = $stats['radreply'] = $stats['radusergroup'] = 0;
        }
        return $stats;
    }

    /**
     * Daftar tabel yang punya kolom tenant_id (dipakai sebagai cascade
     * target). Urutannya nggak penting karena FK constraint udah
     * ON DELETE RESTRICT di tenant_id, jadi kita hapus child rows dulu
     * sebelum tenant. Tabel yang relasinya antar-row di tenant yg sama
     * (e.g. ticket_comments → support_tickets) di-handle pakai cascade
     * lewat DBMS, atau di-delete bareng karena query hapus by tenant_id.
     */
    protected function cascadeTables(): array
    {
        return [
            'ticket_comments',
            'support_tickets',
            'notification_logs',
            'notification_settings',
            'payments',
            'invoices',
            'service_plans',
            'device_assignments',
            'devices_inventory',
            'genieacs_devices',
            'devices_mikrotik',
            'hotspot_vouchers',
            'voucher_batches',
            'audit_logs',
            'customer_profiles',
            'users',
        ];
    }

    /**
     * Cascade delete: hapus semua row yang punya tenant_id = $tenant->id
     * di tabel-tabel cascadeTables(), plus radcheck/radreply/radusergroup
     * yg ke-link ke radius_username pelanggan tenant ini.
     * Return array tbl => deleted_count.
     */
    protected function cascadeDeleteTenantData(Tenant $tenant): array
    {
        $deleted = [];
        DB::transaction(function () use ($tenant, &$deleted) {
            // 1. Cleanup RADIUS rows by username dulu sebelum customer_profiles
            //    di-hapus (kalo customer dihapus dulu, kita hilang reference
            //    radius_username-nya).
            $custUsernames = DB::table('customer_profiles')
                ->where('tenant_id', $tenant->id)
                ->whereNotNull('radius_username')
                ->pluck('radius_username')
                ->all();
            if (!empty($custUsernames)) {
                foreach (['radcheck', 'radreply', 'radusergroup'] as $rt) {
                    if (Schema::hasTable($rt)) {
                        $deleted[$rt] = DB::table($rt)->whereIn('username', $custUsernames)->delete();
                    }
                }
            }

            // 2. Hapus per-tabel tenant_id (urutan child → parent supaya
            //    FK constraint nggak rewel kalau ada).
            foreach ($this->cascadeTables() as $tbl) {
                if (!Schema::hasTable($tbl)) continue;
                $cnt = DB::table($tbl)->where('tenant_id', $tenant->id)->delete();
                if ($cnt > 0) {
                    $deleted[$tbl] = $cnt;
                }
            }
        });
        return $deleted;
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
