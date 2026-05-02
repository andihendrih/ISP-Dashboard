<?php

namespace App\Services\Notifications\Providers;

use App\Services\Notifications\Contracts\WaProvider;
use Illuminate\Support\Facades\Log;

/**
 * Stub provider — gak kirim ke mana-mana.
 * Cuma log + return ok:false. Berguna saat .env credentials belum diisi.
 */
class NullWaProvider implements WaProvider
{
    public function send(string $phone, string $body): array
    {
        Log::info("WA stub (no provider configured) - to: {$phone}", ['body' => substr($body, 0, 80)]);
        return [
            'ok'      => false,
            'message' => 'WA provider belum dikonfigurasi (set WA_PROVIDER + credentials di .env)',
        ];
    }

    public function name(): string
    {
        return 'null';
    }
}
