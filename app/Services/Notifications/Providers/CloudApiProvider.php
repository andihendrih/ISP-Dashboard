<?php

namespace App\Services\Notifications\Providers;

use App\Services\Notifications\Contracts\WaProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Business API via Meta Cloud API.
 * Skeleton — siap dipakai pas verifikasi bisnis Meta selesai.
 */
class CloudApiProvider implements WaProvider
{
    public function __construct(
        private string $token,
        private string $phoneNumberId,
    ) {
    }

    public function send(string $phone, string $body): array
    {
        if (empty($this->token) || empty($this->phoneNumberId)) {
            return ['ok' => false, 'message' => 'WA_CLOUD_TOKEN / WA_CLOUD_PHONE_NUMBER_ID belum diset'];
        }

        try {
            $response = Http::timeout(15)
                ->withToken($this->token)
                ->post("https://graph.facebook.com/v18.0/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $this->normalizePhone($phone),
                    'type'              => 'text',
                    'text'              => ['body' => $body],
                ]);

            return [
                'ok'      => $response->successful(),
                'message' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Cloud API send failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    public function name(): string
    {
        return 'cloud_api';
    }

    private function normalizePhone(string $phone): string
    {
        $p = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($p, '0')) {
            $p = '62' . substr($p, 1);
        } elseif (str_starts_with($p, '8')) {
            $p = '62' . $p;
        }
        return $p;
    }
}
