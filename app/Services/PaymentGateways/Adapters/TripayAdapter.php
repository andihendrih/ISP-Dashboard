<?php

namespace App\Services\PaymentGateways\Adapters;

use App\Services\PaymentGateways\Contracts\PaymentGateway;

class TripayAdapter implements PaymentGateway
{
    public function __construct(
        private string $apiKey,
        private string $privateKey,
        private string $merchantCode = '',
    ) {}

    public function name(): string { return 'tripay'; }
    public function isReady(): bool { return $this->apiKey !== '' && $this->privateKey !== ''; }

    public function createCharge(array $payload): array
    {
        if (!$this->isReady()) {
            return ['ok' => false, 'mode' => 'manual', 'message' => 'Tripay belum dikonfigurasi.'];
        }
        return [
            'ok'           => false,
            'mode'         => 'redirect',
            'message'      => 'Tripay SDK integration belum jadi (stub). Pakai manual transfer dulu.',
            'reference'    => $payload['reference'] ?? null,
        ];
    }
}
