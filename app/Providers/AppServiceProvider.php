<?php

namespace App\Providers;

use App\Services\Notifications\Contracts\WaProvider;
use App\Services\Notifications\Providers\CloudApiProvider;
use App\Services\Notifications\Providers\FonnteProvider;
use App\Services\Notifications\Providers\NullWaProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WaProvider::class, function () {
            $provider = (string) env('WA_PROVIDER', 'null');
            return match ($provider) {
                'fonnte'    => new FonnteProvider((string) env('FONNTE_TOKEN', '')),
                'cloud_api' => new CloudApiProvider(
                    (string) env('WA_CLOUD_TOKEN', ''),
                    (string) env('WA_CLOUD_PHONE_NUMBER_ID', ''),
                ),
                default     => new NullWaProvider(),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
