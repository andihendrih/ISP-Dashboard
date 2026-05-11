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
        $fromEmail = $creds['from']['email'] ?? config('mail.from.address');
        $fromName  = $creds['from']['name']  ?? $setting->effectiveBrandName();

        $dsn = self::buildDsn($setting->email_provider, $creds);
        if (!$dsn) return Mail::mailer();

        try {
            $transport = Transport::fromDsn($dsn);
        } catch (\Throwable $e) {
            return Mail::mailer();
        }

        $mailer = new Mailer(
            'tenant_'.$tenant->id,
            app('view'),
            $transport,
            app('events'),
        );
        if ($fromEmail) {
            $mailer->alwaysFrom($fromEmail, $fromName);
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
