<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramBotService
{
    public function sendMessage(int|string $chatId, string $text, array $extra = []): bool
    {
        return $this->request('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $extra));
    }

    public function editMessageText(int|string $chatId, int $messageId, string $text, array $extra = []): bool
    {
        return $this->request('editMessageText', array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
        ], $extra));
    }

    public function editMessageMedia(int|string $chatId, int $messageId, array $media, array $extra = []): bool
    {
        return $this->request('editMessageMedia', array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'media' => json_encode($media, JSON_UNESCAPED_UNICODE),
        ], $extra));
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): bool
    {
        $payload = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert,
        ];

        if ($text !== null) {
            $payload['text'] = $text;
        }

        return $this->request('answerCallbackQuery', $payload);
    }

    public function sendChatAction(int|string $chatId, string $action): bool
    {
        return $this->request('sendChatAction', [
            'chat_id' => $chatId,
            'action' => $action,
        ]);
    }

    private function request(string $method, array $payload): bool
    {
        $token = (string) config('telegram.bot_token');

        if ($token === '') {
            Log::channel('telegram')->error('telegram.api.missing_token', ['method' => $method]);

            return false;
        }

        if (isset($payload['reply_markup']) && is_array($payload['reply_markup'])) {
            $payload['reply_markup'] = json_encode($payload['reply_markup'], JSON_UNESCAPED_UNICODE);
        }

        try {
            $response = Http::baseUrl("https://api.telegram.org/bot{$token}")
                ->timeout((int) config('telegram.request_timeout', 10))
                ->acceptJson()
                ->asForm()
                ->post($method, $payload);

            if (! $response->successful()) {
                Log::channel('telegram')->warning('telegram.api.request_failed', [
                    'method' => $method,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return (bool) $response->json('ok', false);
        } catch (Throwable $e) {
            Log::channel('telegram')->error('telegram.api.exception', [
                'method' => $method,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
