<?php

namespace App\Services\Notifications\Contracts;

interface WaProvider
{
    /**
     * Kirim pesan WA. Return array dengan keys:
     *   - ok: bool
     *   - message: string (response dari provider, untuk audit)
     */
    public function send(string $phone, string $body): array;

    public function name(): string;
}
