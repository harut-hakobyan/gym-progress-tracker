<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramWebhookService
{
    public function setWebhook(): bool
    {
        $url = (string) config('telegram.webhook_url');
        $secret = (string) config('telegram.webhook_secret');

        if ($url === '' || $secret === '') {
            return false;
        }

        return $this->request('setWebhook', [
            'url' => rtrim($url, '/').'/api/telegram/webhook/'.$secret,
            'secret_token' => $secret,
            'drop_pending_updates' => false,
        ]);
    }

    public function deleteWebhook(): bool
    {
        return $this->request('deleteWebhook', [
            'drop_pending_updates' => false,
        ]);
    }

    public function getWebhookInfo(): array|null
    {
        $token = (string) config('telegram.bot_token');

        if ($token === '') {
            return null;
        }

        try {
            $response = Http::baseUrl("https://api.telegram.org/bot{$token}")
                ->timeout((int) config('telegram.request_timeout', 10))
                ->acceptJson()
                ->get('getWebhookInfo');

            if (! $response->successful()) {
                Log::channel('telegram')->warning('telegram.webhook.info_failed', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            return (array) $response->json('result', []);
        } catch (Throwable $e) {
            Log::channel('telegram')->error('telegram.webhook.info_exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function request(string $method, array $payload): bool
    {
        $token = (string) config('telegram.bot_token');

        if ($token === '') {
            return false;
        }

        try {
            $response = Http::baseUrl("https://api.telegram.org/bot{$token}")
                ->timeout((int) config('telegram.request_timeout', 10))
                ->acceptJson()
                ->asJson()
                ->post($method, $payload);

            return $response->successful() && (bool) $response->json('ok', false);
        } catch (Throwable $e) {
            Log::channel('telegram')->error('telegram.webhook.exception', [
                'method' => $method,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
