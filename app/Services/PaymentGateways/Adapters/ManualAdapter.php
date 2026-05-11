<?php

namespace App\Services\PaymentGateways\Adapters;

use App\Services\PaymentGateways\Contracts\PaymentGateway;

class ManualAdapter implements PaymentGateway
{
    /** @param array<int, array{bank:string, account:string, name:string}> $banks */
    public function __construct(private array $banks = [])
    {
    }

    public function name(): string
    {
        return 'manual';
    }

    public function isReady(): bool
    {
        return !empty($this->banks);
    }

    public function createCharge(array $payload): array
    {
        $lines = [];
        foreach ($this->banks as $b) {
            $lines[] = sprintf('%s %s a/n %s', $b['bank'] ?? '?', $b['account'] ?? '?', $b['name'] ?? '?');
        }
        return [
            'ok'           => true,
            'mode'         => 'manual',
            'url'          => null,
            'va'           => null,
            'instructions' => $lines ? implode("\n", $lines) : 'Hubungi admin untuk info rekening.',
            'reference'    => $payload['reference'] ?? null,
            'message'      => 'Manual transfer — upload bukti bayar setelah transfer.',
        ];
    }

    public function banks(): array
    {
        return $this->banks;
    }
}
