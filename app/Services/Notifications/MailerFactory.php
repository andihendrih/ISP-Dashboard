<?php

namespace App\Services\Notifications;

use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport;

/**
 * Bikin Mailer instance khusus tenant. Kalau setting kosong / null,
 * fallback ke Mailer default (config/mail.php).
 *
 * Pakai:
 *   $mailer = MailerFactory::forTenant($tenant);
 *   $mailer->to($email)->send(new InvoiceMail(...));
 */
class MailerFactory
{
    public static function forTenant(?Tenant $tenant)
    {
        if (!$tenant) return Mail::mailer();
        $setting = TenantSetting::firstWhere('tenant_id', $tenant->id);
        if (!$setting || $setting->email_provider === 'null') {
            return Mail::mailer();
        }
        $creds = $setting->emailCreds();
        $fromName = $creds['from']['name'] ?? $setting->effectiveBrandName();
        return self::buildMailer('tenant_'.$tenant->id, $setting->email_provider, $creds, $fromName)
            ?? Mail::mailer();
    }

    /** Bikin Mailer dari PlatformSetting (untuk reminder superadmin ke tenant). */
    public static function forPlatform(\App\Models\PlatformSetting $setting)
    {
        if ($setting->email_provider === 'null') return Mail::mailer();
        $creds = $setting->emailCreds();
        $fromName = $creds['from']['name'] ?? $setting->effectiveBrandName();
        return self::buildMailer('platform', $setting->email_provider, $creds, $fromName)
            ?? Mail::mailer();
    }

    private static function buildMailer(string $name, string $provider, array $creds, ?string $fromName)
    {
        $fromEmail = $creds['from']['email'] ?? config('mail.from.address');
        $dsn = self::buildDsn($provider, $creds);
        if (!$dsn) return null;
        try {
            $transport = Transport::fromDsn($dsn);
        } catch (\Throwable $e) {
            return null;
        }
        $mailer = new Mailer($name, app('view'), $transport, app('events'));
        if ($fromEmail) {
            $mailer->alwaysFrom($fromEmail, $fromName ?: $fromEmail);
        }
        return $mailer;
    }

    private static function buildDsn(string $provider, array $creds): ?string
    {
        $u = fn (?string $v) => $v === null ? '' : rawurlencode($v);
        switch ($provider) {
            case 'smtp':
                $s = $creds['smtp'] ?? [];
                $host = $s['host'] ?? '';
                $port = (int) ($s['port'] ?? 587);
                if (!$host) return null;
                $enc  = $s['encryption'] ?? 'tls';
                $scheme = $enc === 'ssl' ? 'smtps' : 'smtp';
                $user = $u($s['username'] ?? '');
                $pass = $u($s['password'] ?? '');
                $auth = $user !== '' ? "$user:$pass@" : '';
                return "{$scheme}://{$auth}{$host}:{$port}";
            case 'sendgrid':
                $key = $creds['sendgrid']['api_key'] ?? '';
                if (!$key) return null;
                return 'sendgrid+api://'.$u($key).'@default';
            case 'mailgun':
                $dom = $creds['mailgun']['domain'] ?? '';
                $sec = $creds['mailgun']['secret'] ?? '';
                if (!$dom || !$sec) return null;
                return 'mailgun+api://'.$u($sec).':'.$u($dom).'@default';
        }
        return null;
    }
}
