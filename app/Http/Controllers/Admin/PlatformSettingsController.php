<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\Notifications\MailerFactory;
use App\Services\Notifications\Providers\CloudApiProvider;
use App\Services\Notifications\Providers\FonnteProvider;
use App\Services\Notifications\Providers\NullWaProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Platform-level settings (superadmin only).
 *
 * Beda dari TenantSettingsController:
 *  - Tenant settings = per-tenant, dipakai tenant utk nagih customer-nya
 *  - Platform settings = single-row, dipakai SUPERADMIN utk nagih tenant (SaaS billing)
 *
 * Route protected via middleware role:superadmin (lihat web.php).
 */
class PlatformSettingsController extends Controller
{
    public function index(): View { return $this->showTab('brand'); }
    public function brand(): View { return $this->showTab('brand'); }
    public function bank(): View  { return $this->showTab('bank'); }
    public function whatsapp(): View { return $this->showTab('whatsapp'); }
    public function email(): View { return $this->showTab('email'); }

    protected function showTab(string $tab): View
    {
        $setting = PlatformSetting::current();
        return view('admin.platform-settings.index', compact('setting', 'tab'));
    }

    public function updateBrand(Request $request): RedirectResponse
    {
        $setting = PlatformSetting::current();
        $data = $request->validate([
            'brand_name'    => ['nullable', 'string', 'max:120'],
            'brand_address' => ['nullable', 'string', 'max:255'],
            'brand_phone'   => ['nullable', 'string', 'max:60'],
            'brand_email'   => ['nullable', 'email', 'max:120'],
            'brand_tagline' => ['nullable', 'string', 'max:500'],
            'brand_logo'    => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
        ]);
        if ($request->hasFile('brand_logo')) {
            $data['brand_logo_path'] = $request->file('brand_logo')->store('platform-brand', 'public');
        }
        unset($data['brand_logo']);
        $setting->update($data);
        return back()->with('success', 'Brand platform tersimpan.');
    }

    public function updateBank(Request $request): RedirectResponse
    {
        $setting = PlatformSetting::current();
        $data = $request->validate([
            'bank_info' => ['nullable', 'string', 'max:2000'],
        ]);
        $setting->update($data);
        return back()->with('success', 'Bank info platform tersimpan.');
    }

    public function updateWhatsapp(Request $request): RedirectResponse
    {
        $setting = PlatformSetting::current();
        $data = $request->validate([
            'wa_provider'       => ['required', 'in:null,fonnte,cloudapi'],
            'fonnte_token'      => ['nullable', 'string', 'max:200'],
            'cloudapi_token'    => ['nullable', 'string', 'max:500'],
            'cloudapi_phone_id' => ['nullable', 'string', 'max:50'],
            'cloudapi_version'  => ['nullable', 'string', 'max:10'],
        ]);
        $existing = $setting->waCreds();
        $creds = [
            'fonnte' => [
                'token' => $data['fonnte_token'] ?? $existing['fonnte']['token'] ?? null,
            ],
            'cloudapi' => [
                'token'    => $data['cloudapi_token']    ?? $existing['cloudapi']['token'] ?? null,
                'phone_id' => $data['cloudapi_phone_id'] ?? $existing['cloudapi']['phone_id'] ?? null,
                'version'  => $data['cloudapi_version']  ?? $existing['cloudapi']['version'] ?? 'v18.0',
            ],
        ];
        $setting->wa_provider    = $data['wa_provider'];
        $setting->wa_credentials = $creds;
        $setting->save();
        return back()->with('success', 'WhatsApp platform tersimpan.');
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $setting = PlatformSetting::current();
        $data = $request->validate([
            'email_provider'   => ['required', 'in:null,smtp,sendgrid,mailgun'],
            'smtp_host'        => ['nullable', 'string', 'max:120'],
            'smtp_port'        => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username'    => ['nullable', 'string', 'max:120'],
            'smtp_password'    => ['nullable', 'string', 'max:200'],
            'smtp_encryption'  => ['nullable', 'in:none,tls,ssl'],
            'from_email'       => ['nullable', 'email', 'max:120'],
            'from_name'        => ['nullable', 'string', 'max:120'],
            'sendgrid_api_key' => ['nullable', 'string', 'max:200'],
            'mailgun_domain'   => ['nullable', 'string', 'max:120'],
            'mailgun_secret'   => ['nullable', 'string', 'max:200'],
        ]);
        $existing = $setting->emailCreds();
        $smtpPassword = $data['smtp_password'] ?? null;
        if (!$smtpPassword && isset($existing['smtp']['password'])) {
            $smtpPassword = $existing['smtp']['password'];
        }
        $creds = [
            'smtp' => [
                'host'       => $data['smtp_host']       ?? $existing['smtp']['host'] ?? null,
                'port'       => $data['smtp_port']       ?? $existing['smtp']['port'] ?? null,
                'username'   => $data['smtp_username']   ?? $existing['smtp']['username'] ?? null,
                'password'   => $smtpPassword,
                'encryption' => $data['smtp_encryption'] ?? $existing['smtp']['encryption'] ?? 'tls',
            ],
            'from' => [
                'email' => $data['from_email'] ?? $existing['from']['email'] ?? null,
                'name'  => $data['from_name']  ?? $existing['from']['name']  ?? null,
            ],
            'sendgrid' => [
                'api_key' => $data['sendgrid_api_key'] ?? $existing['sendgrid']['api_key'] ?? null,
            ],
            'mailgun' => [
                'domain' => $data['mailgun_domain'] ?? $existing['mailgun']['domain'] ?? null,
                'secret' => $data['mailgun_secret'] ?? $existing['mailgun']['secret'] ?? null,
            ],
        ];
        $setting->email_provider    = $data['email_provider'];
        $setting->email_credentials = $creds;
        $setting->save();
        return back()->with('success', 'Email platform tersimpan.');
    }

    /** Test kirim WA pakai provider platform. */
    public function testWa(Request $request): RedirectResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:30']]);
        $wa = self::resolveWa();
        $result = $wa->send($data['phone'], 'Test koneksi WA platform — kirim dari ' . config('app.name'));
        if ($result['ok']) {
            return back()->with('success', "Test WA terkirim ke {$data['phone']}.");
        }
        return back()->with('error', 'Test WA gagal: ' . substr((string) $result['message'], 0, 200));
    }

    /** Test kirim email pakai mailer platform. */
    public function testEmail(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:120']]);
        $mailer = self::resolveMailer();
        try {
            $mailer->to($data['email'])->send(new \App\Mail\InvoiceNotificationMail(
                'Test Email Platform',
                'Halo, ini test email dari ' . config('app.name') . '.'
            ));
            return back()->with('success', "Test email terkirim ke {$data['email']}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Test email gagal: ' . substr($e->getMessage(), 0, 200));
        }
    }

    /** Static helper supaya command line / service lain juga bisa pakai. */
    public static function resolveWa(): \App\Services\Notifications\Contracts\WaProvider
    {
        $s = PlatformSetting::current();
        $creds = $s->waCreds();
        return match ($s->wa_provider) {
            'fonnte'   => new FonnteProvider((string) ($creds['fonnte']['token'] ?? '')),
            'cloudapi' => new CloudApiProvider(
                (string) ($creds['cloudapi']['token']    ?? ''),
                (string) ($creds['cloudapi']['phone_id'] ?? ''),
            ),
            default    => new NullWaProvider(),
        };
    }

    public static function resolveMailer()
    {
        $s = PlatformSetting::current();
        if ($s->email_provider === 'null') return \Illuminate\Support\Facades\Mail::mailer();
        return MailerFactory::forPlatform($s);
    }
}
