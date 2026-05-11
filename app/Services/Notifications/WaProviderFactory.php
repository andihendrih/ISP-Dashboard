<?php

namespace App\Services\Notifications;

use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Services\Notifications\Contracts\WaProvider;
use App\Services\Notifications\Providers\CloudApiProvider;
use App\Services\Notifications\Providers\FonnteProvider;
use App\Services\Notifications\Providers\NullWaProvider;

/**
 * Resolve WA provider berdasarkan setting tenant. Kalau tenant null atau gak
 * punya setting (atau provider=null), fallback ke config global di .env.
 */
class WaProviderFactory
{
    public static function forTenant(?Tenant $tenant): WaProvider
    {
        if (!$tenant) {
            return self::globalProvider();
        }
        $setting = TenantSetting::firstWhere('tenant_id', $tenant->id);
        if (!$setting || $setting->wa_provider === 'null') {
            return self::globalProvider();
        }

        $creds = $setting->waCreds();
        return match ($setting->wa_provider) {
            'fonnte'    => new FonnteProvider((string) ($creds['fonnte']['token'] ?? '')),
            'cloudapi'  => new CloudApiProvider(
                (string) ($creds['cloudapi']['token'] ?? ''),
                (string) ($creds['cloudapi']['phone_id'] ?? ''),
            ),
            default     => new NullWaProvider(),
        };
    }

    public static function forTenantId(?int $tenantId): WaProvider
    {
        if (!$tenantId) return self::globalProvider();
        return self::forTenant(Tenant::find($tenantId));
    }

    public static function globalProvider(): WaProvider
    {
        $provider = (string) env('WA_PROVIDER', 'null');
        return match ($provider) {
            'fonnte'    => new FonnteProvider((string) env('FONNTE_TOKEN', '')),
            'cloud_api' => new CloudApiProvider(
                (string) env('WA_CLOUD_TOKEN', ''),
                (string) env('WA_CLOUD_PHONE_NUMBER_ID', ''),
            ),
            default     => new NullWaProvider(),
        };
    }
}
