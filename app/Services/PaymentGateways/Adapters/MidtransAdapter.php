<?php

namespace App\Services\PaymentGateways\Adapters;

use App\Services\PaymentGateways\Contracts\PaymentGateway;

/**
 * Stub Midtrans — credential di-store tapi SDK actual nyusul (Phase berikutnya).
 * createCharge() return 'not_implemented' supaya caller fallback ke manual.
 */
class MidtransAdapter implements PaymentGateway
{
    public function __construct(
        private string $serverKey,
        private string $clientKey,
        private string $merchantId = '',
        private bool $isProduction = false,
    ) {}

    public function name(): string { return 'midtrans'; }

    public function isReady(): bool { return $this->serverKey !== '' && $this->clientKey !== ''; }

    public function createCharge(array $payload): array
    {
        if (!$this->isReady()) {
            return ['ok' => false, 'mode' => 'manual', 'message' => 'Midtrans belum dikonfigurasi.'];
        }
        return [
            'ok'           => false,
            'mode'         => 'redirect',
            'url'          => null,
            'va'           => null,
            'instructions' => null,
            'reference'    => $payload['reference'] ?? null,
            'message'      => 'Midtrans SDK integration belum jadi (stub). Pakai manual transfer dulu.',
        ];
    }

    public function isProduction(): bool { return $this->isProduction; }
}
