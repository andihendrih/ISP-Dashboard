<?php

namespace App\Services\PaymentGateways;

use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Services\PaymentGateways\Adapters\ManualAdapter;
use App\Services\PaymentGateways\Adapters\MidtransAdapter;
use App\Services\PaymentGateways\Adapters\TripayAdapter;
use App\Services\PaymentGateways\Adapters\XenditAdapter;
use App\Services\PaymentGateways\Contracts\PaymentGateway;

class PaymentGatewayFactory
{
    /**
     * Resolve adapter default gateway untuk tenant. Fallback ke Manual (banks dari setting).
     */
    public static function defaultForTenant(?Tenant $tenant): PaymentGateway
    {
        if (!$tenant) return new ManualAdapter([]);
        $setting = TenantSetting::firstWhere('tenant_id', $tenant->id);
        if (!$setting) return new ManualAdapter([]);

        $gw = self::resolve($setting, $setting->payment_gateway_default);
        return $gw?->isReady() ? $gw : new ManualAdapter(self::manualBanks($setting));
    }

    /** Resolve spesifik nama gateway untuk tenant. */
    public static function forTenant(?Tenant $tenant, string $gateway): PaymentGateway
    {
        if (!$tenant) return new ManualAdapter([]);
        $setting = TenantSetting::firstWhere('tenant_id', $tenant->id);
        if (!$setting) return new ManualAdapter([]);
        return self::resolve($setting, $gateway) ?? new ManualAdapter(self::manualBanks($setting));
    }

    /** Daftar gateway yg di-enable di setting. */
    public static function enabledForTenant(?Tenant $tenant): array
    {
        if (!$tenant) return ['manual'];
        $s = TenantSetting::firstWhere('tenant_id', $tenant->id);
        if (!$s) return ['manual'];
        return array_values(array_filter([
            $s->payment_manual_enabled   ? 'manual'   : null,
            $s->payment_midtrans_enabled ? 'midtrans' : null,
            $s->payment_xendit_enabled   ? 'xendit'   : null,
            $s->payment_tripay_enabled   ? 'tripay'   : null,
        ]));
    }

    private static function resolve(TenantSetting $s, string $name): ?PaymentGateway
    {
        $creds = $s->paymentCreds();
        return match ($name) {
            'midtrans' => new MidtransAdapter(
                (string) ($creds['midtrans']['server_key']    ?? ''),
                (string) ($creds['midtrans']['client_key']    ?? ''),
                (string) ($creds['midtrans']['merchant_id']   ?? ''),
                (bool)   ($creds['midtrans']['is_production'] ?? false),
            ),
            'xendit' => new XenditAdapter(
                (string) ($creds['xendit']['secret_key']     ?? ''),
                (string) ($creds['xendit']['callback_token'] ?? ''),
            ),
            'tripay' => new TripayAdapter(
                (string) ($creds['tripay']['api_key']       ?? ''),
                (string) ($creds['tripay']['private_key']   ?? ''),
                (string) ($creds['tripay']['merchant_code'] ?? ''),
            ),
            'manual' => new ManualAdapter(self::manualBanks($s)),
            default  => null,
        };
    }

    private static function manualBanks(TenantSetting $s): array
    {
        $creds = $s->paymentCreds();
        return $creds['manual']['bank_accounts'] ?? [];
    }
}
