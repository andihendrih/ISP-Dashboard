<?php

namespace App\Services\PaymentGateways\Contracts;

interface PaymentGateway
{
    /** Slug provider: manual/midtrans/xendit/tripay */
    public function name(): string;

    /** Apakah gateway dianggap ready (credential cukup). */
    public function isReady(): bool;

    /**
     * Bikin "payment intent" — return URL bayar / instruksi transfer.
     * Output minimal:
     *   - ok: bool
     *   - mode: 'redirect'|'manual'|'va'
     *   - url: ?string
     *   - va: ?array (kalau VA, bank+number+expire)
     *   - instructions: ?string (kalau manual)
     *   - reference: ?string
     *   - message: string
     *
     * Adapter SDK actual nyusul; sekarang Manual aja yg implement.
     */
    public function createCharge(array $payload): array;
}
