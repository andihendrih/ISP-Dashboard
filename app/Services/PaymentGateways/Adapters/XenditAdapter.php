<?php

namespace App\Services\PaymentGateways\Adapters;

use App\Services\PaymentGateways\Contracts\PaymentGateway;

class XenditAdapter implements PaymentGateway
{
    public function __construct(
        private string $secretKey,
        private string $callbackToken = '',
    ) {}

    public function name(): string { return 'xendit'; }
    public function isReady(): bool { return $this->secretKey !== ''; }

    public function createCharge(array $payload): array
    {
        if (!$this->isReady()) {
            return ['ok' => false, 'mode' => 'manual', 'message' => 'Xendit belum dikonfigurasi.'];
        }
        return [
            'ok'           => false,
            'mode'         => 'redirect',
            'message'      => 'Xendit SDK integration belum jadi (stub). Pakai manual transfer dulu.',
            'reference'    => $payload['reference'] ?? null,
        ];
    }
}
