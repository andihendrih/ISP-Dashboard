<?php

namespace App\Services\Notifications\Providers;

use App\Services\Notifications\Contracts\WaProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteProvider implements WaProvider
{
    public function __construct(private string $token)
    {
    }

    public function send(string $phone, string $body): array
    {
        if (empty($this->token)) {
            return ['ok' => false, 'message' => 'FONNTE_TOKEN belum diset di .env'];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => $this->token])
                ->asForm()
                ->post('https://api.fonnte.com/send', [
                    'target'      => $this->normalizePhone($phone),
                    'message'     => $body,
                    'countryCode' => '62',
                ]);

            $body = $response->body();
            $json = $response->json();

            $ok = $response->successful() && (($json['status'] ?? false) === true);
            return ['ok' => $ok, 'message' => $body];
        } catch (\Throwable $e) {
            Log::warning('Fonnte send failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    public function name(): string
    {
        return 'fonnte';
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
