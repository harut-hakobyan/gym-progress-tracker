<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('telegram-webhook', function (Request $request): array {
            return [
                Limit::perMinute((int) config('telegram.webhook_rate_limit_per_minute', 120))
                    ->by('telegram-webhook'),
            ];
        });
    }
}
