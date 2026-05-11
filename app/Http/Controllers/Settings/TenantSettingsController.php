<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TenantSetting;
use App\Services\Notifications\NotificationService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Settings tenant — tenant admin & finance ngeset brand, WA, email, gateway
 * milik tenant mereka sendiri.
 *
 * Superadmin yg lagi view-as tenant juga bisa edit (sesuai tenant aktif di TenantContext).
 * Route protected via middleware role:admin,finance,superadmin (lihat web.php).
 */
class TenantSettingsController extends Controller
{
    /** Tab landing — defaults ke 'brand'. */
    public function index(): View { return $this->showTab('brand'); }
    public function brand(): View { return $this->showTab('brand'); }
    public function whatsapp(): View { return $this->showTab('whatsapp'); }
    public function email(): View { return $this->showTab('email'); }
    public function gateway(): View { return $this->showTab('gateway'); }

    protected function showTab(string $tab): View
    {
        $tenant = $this->activeTenant();
        $setting = TenantSetting::forTenant($tenant->id);
        return view('settings.tenant.index', compact('tenant', 'setting', 'tab'));
    }

    public function updateBrand(Request $request): RedirectResponse
    {
        $tenant = $this->activeTenant();
        $setting = TenantSetting::forTenant($tenant->id);

        $data = $request->validate([
            'brand_name'      => ['nullable', 'string', 'max:120'],
            'brand_address'   => ['nullable', 'string', 'max:255'],
            'brand_phone'     => ['nullable', 'string', 'max:60'],
            'brand_email'     => ['nullable', 'email', 'max:120'],
            'brand_npwp'      => ['nullable', 'string', 'max:30'],
            'brand_bank_info' => ['nullable', 'string', 'max:2000'],
            'brand_logo'      => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
        ]);
        if ($request->hasFile('brand_logo')) {
            $data['brand_logo_path'] = $request->file('brand_logo')->store('tenant-brands', 'public');
        }
        unset($data['brand_logo']);
        $setting->update($data);
        return back()->with('success', 'Brand setting tersimpan.');
    }

    public function updateWhatsapp(Request $request): RedirectResponse
    {
        $tenant = $this->activeTenant();
        $setting = TenantSetting::forTenant($tenant->id);

        $data = $request->validate([
            'wa_provider'      => ['required', 'in:null,fonnte,cloudapi'],
            'fonnte_token'     => ['nullable', 'string', 'max:200'],
            'cloudapi_token'   => ['nullable', 'string', 'max:500'],
            'cloudapi_phone_id'=> ['nullable', 'string', 'max:50'],
            'cloudapi_version' => ['nullable', 'string', 'max:10'],
        ]);

        $creds = [
            'fonnte' => [
                'token' => $data['fonnte_token'] ?? null,
            ],
            'cloudapi' => [
                'token'    => $data['cloudapi_token'] ?? null,
                'phone_id' => $data['cloudapi_phone_id'] ?? null,
                'version'  => $data['cloudapi_version'] ?? 'v18.0',
            ],
        ];
        $setting->wa_provider    = $data['wa_provider'];
        $setting->wa_credentials = $creds;
        $setting->save();
        return back()->with('success', 'WhatsApp setting tersimpan.');
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $tenant = $this->activeTenant();
        $setting = TenantSetting::forTenant($tenant->id);

        $data = $request->validate([
            'email_provider'  => ['required', 'in:null,smtp,sendgrid,mailgun'],
            'smtp_host'       => ['nullable', 'string', 'max:120'],
            'smtp_port'       => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username'   => ['nullable', 'string', 'max:120'],
            'smtp_password'   => ['nullable', 'string', 'max:200'],
            'smtp_encryption' => ['nullable', 'in:none,tls,ssl'],
            'from_email'      => ['nullable', 'email', 'max:120'],
            'from_name'       => ['nullable', 'string', 'max:120'],
            'sendgrid_api_key'=> ['nullable', 'string', 'max:200'],
            'mailgun_domain'  => ['nullable', 'string', 'max:120'],
            'mailgun_secret'  => ['nullable', 'string', 'max:200'],
        ]);

        // Re-use existing password jika user gak isi (biar gak overwrite blank)
        $existing = $setting->emailCreds();
        $smtpPassword = $data['smtp_password'] ?? null;
        if (!$smtpPassword && isset($existing['smtp']['password'])) {
            $smtpPassword = $existing['smtp']['password'];
        }

        $creds = [
            'smtp' => [
                'host'       => $data['smtp_host']       ?? null,
                'port'       => $data['smtp_port']       ?? null,
                'username'   => $data['smtp_username']   ?? null,
                'password'   => $smtpPassword,
                'encryption' => $data['smtp_encryption'] ?? 'tls',
            ],
            'from' => [
                'email' => $data['from_email'] ?? null,
                'name'  => $data['from_name']  ?? null,
            ],
            'sendgrid' => [
                'api_key' => $data['sendgrid_api_key'] ?? $existing['sendgrid']['api_key'] ?? null,
            ],
            'mailgun' => [
                'domain' => $data['mailgun_domain']  ?? null,
                'secret' => $data['mailgun_secret']  ?? $existing['mailgun']['secret'] ?? null,
            ],
        ];
        $setting->email_provider    = $data['email_provider'];
        $setting->email_credentials = $creds;
        $setting->save();
        return back()->with('success', 'Email setting tersimpan.');
    }

    public function updateGateway(Request $request): RedirectResponse
    {
        $tenant = $this->activeTenant();
        $setting = TenantSetting::forTenant($tenant->id);

        $data = $request->validate([
            'payment_gateway_default' => ['required', 'in:manual,midtrans,xendit,tripay'],
            'payment_midtrans_enabled' => ['nullable', 'boolean'],
            'payment_xendit_enabled'   => ['nullable', 'boolean'],
            'payment_tripay_enabled'   => ['nullable', 'boolean'],
            'payment_manual_enabled'   => ['nullable', 'boolean'],

            // Midtrans
            'midtrans_server_key'   => ['nullable', 'string', 'max:200'],
            'midtrans_client_key'   => ['nullable', 'string', 'max:200'],
            'midtrans_merchant_id'  => ['nullable', 'string', 'max:60'],
            'midtrans_is_production'=> ['nullable', 'boolean'],

            // Xendit
            'xendit_secret_key'     => ['nullable', 'string', 'max:200'],
            'xendit_callback_token' => ['nullable', 'string', 'max:200'],

            // Tripay
            'tripay_api_key'   => ['nullable', 'string', 'max:200'],
            'tripay_private_key'=> ['nullable', 'string', 'max:200'],
            'tripay_merchant_code' => ['nullable', 'string', 'max:60'],

            // Manual bank accounts (CSV: bank|account|name; multi-line)
            'manual_bank_accounts' => ['nullable', 'string', 'max:2000'],
        ]);

        $existing = $setting->paymentCreds();

        $creds = [
            'midtrans' => [
                'server_key'    => $data['midtrans_server_key']    ?? $existing['midtrans']['server_key']    ?? null,
                'client_key'    => $data['midtrans_client_key']    ?? $existing['midtrans']['client_key']    ?? null,
                'merchant_id'   => $data['midtrans_merchant_id']   ?? null,
                'is_production' => (bool) ($data['midtrans_is_production'] ?? false),
            ],
            'xendit' => [
                'secret_key'     => $data['xendit_secret_key']     ?? $existing['xendit']['secret_key']     ?? null,
                'callback_token' => $data['xendit_callback_token'] ?? $existing['xendit']['callback_token'] ?? null,
            ],
            'tripay' => [
                'api_key'        => $data['tripay_api_key']        ?? $existing['tripay']['api_key']        ?? null,
                'private_key'    => $data['tripay_private_key']    ?? $existing['tripay']['private_key']    ?? null,
                'merchant_code'  => $data['tripay_merchant_code']  ?? null,
            ],
            'manual' => [
                'bank_accounts' => $this->parseBankAccounts($data['manual_bank_accounts'] ?? ''),
            ],
        ];

        $setting->payment_gateway_default  = $data['payment_gateway_default'];
        $setting->payment_midtrans_enabled = (bool) ($data['payment_midtrans_enabled'] ?? false);
        $setting->payment_xendit_enabled   = (bool) ($data['payment_xendit_enabled']   ?? false);
        $setting->payment_tripay_enabled   = (bool) ($data['payment_tripay_enabled']   ?? false);
        $setting->payment_manual_enabled   = (bool) ($data['payment_manual_enabled']   ?? false);
        $setting->payment_credentials      = $creds;
        $setting->save();
        return back()->with('success', 'Payment gateway setting tersimpan.');
    }

    /** Parse multi-line: "BCA|1234567890|PT Test" → array */
    protected function parseBankAccounts(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\r?\n/', trim($raw)) as $line) {
            $line = trim($line);
            if (!$line) continue;
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 3) {
                $out[] = ['bank' => $parts[0], 'account' => $parts[1], 'name' => $parts[2]];
            }
        }
        return $out;
    }

    /**
     * Resolve tenant aktif: untuk tenant admin/finance = tenant mereka;
     * untuk superadmin = tenant yg lagi di view-as (kalo gak ada, default-nya tenant ahnet).
     */
    /** Tombol "Test" di tab WhatsApp — kirim test pakai provider tenant aktif. */
    public function testWa(Request $request, NotificationService $notif): RedirectResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:30']]);
        $tenant = $this->activeTenant();
        $log = $notif->sendTestWaForTenant($tenant->id, $data['phone']);
        if ($log->status === 'sent') {
            return back()->with('success', "Test WA terkirim ke {$data['phone']}.");
        }
        return back()->with('error', 'Test WA gagal: ' . substr($log->provider_response, 0, 200));
    }

    /** Tombol "Test" di tab Email — kirim test pakai mailer tenant aktif. */
    public function testEmail(Request $request, NotificationService $notif): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:120']]);
        $tenant = $this->activeTenant();
        $log = $notif->sendTestEmailForTenant($tenant->id, $data['email']);
        if ($log->status === 'sent') {
            return back()->with('success', "Test email terkirim ke {$data['email']}.");
        }
        return back()->with('error', 'Test email gagal: ' . substr($log->provider_response, 0, 200));
    }

    protected function activeTenant(): \App\Models\Tenant
    {
        $ctx = app(TenantContext::class);
        $tenantId = $ctx->tenantId();
        if (!$tenantId) {
            $tenantId = Auth::user()?->tenant_id;
        }
        abort_if(!$tenantId, 403, 'Tenant tidak terdeteksi.');
        return \App\Models\Tenant::findOrFail($tenantId);
    }
}
