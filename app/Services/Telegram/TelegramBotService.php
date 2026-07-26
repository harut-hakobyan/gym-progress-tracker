<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramBotService
{
    public function sendMessage(int|string $chatId, string $text, array $extra = []): bool
    {
        return $this->requestResult('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $extra))['successful'];
    }

    public function sendMessageResult(int|string $chatId, string $text, array $extra = []): array
    {
        return $this->requestResult('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $extra));
    }

    public function editMessageText(int|string $chatId, int $messageId, string $text, array $extra = []): bool
    {
        return $this->requestResult('editMessageText', array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
        ], $extra))['successful'];
    }

    public function editMessageTextResult(int|string $chatId, int $messageId, string $text, array $extra = []): array
    {
        return $this->requestResult('editMessageText', array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
        ], $extra));
    }

    public function editMessageMedia(int|string $chatId, int $messageId, array $media, array $extra = []): bool
    {
        return $this->requestResult('editMessageMedia', array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'media' => json_encode($media, JSON_UNESCAPED_UNICODE),
        ], $extra))['successful'];
    }

    public function deleteMessage(int|string $chatId, int $messageId): bool
    {
        return $this->requestResult('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ])['successful'];
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

        return $this->requestResult('answerCallbackQuery', $payload)['successful'];
    }

    public function sendChatAction(int|string $chatId, string $action): bool
    {
        return $this->requestResult('sendChatAction', [
            'chat_id' => $chatId,
            'action' => $action,
        ])['successful'];
    }

    /**
     * @return array{successful:bool,status:int,body:string,json:array<string,mixed>}
     */
    private function requestResult(string $method, array $payload): array
    {
        $token = (string) config('telegram.bot_token');

        if ($token === '') {
            Log::channel('telegram')->error('telegram.api.missing_token', ['method' => $method]);

            return [
                'successful' => false,
                'status' => 0,
                'body' => '',
                'json' => [],
            ];
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

                return [
                    'successful' => false,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => (array) $response->json(),
                ];
            }

            return [
                'successful' => (bool) $response->json('ok', false),
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => (array) $response->json(),
            ];
        } catch (Throwable $e) {
            Log::channel('telegram')->error('telegram.api.exception', [
                'method' => $method,
                'message' => $e->getMessage(),
            ]);

            return [
                'successful' => false,
                'status' => 0,
                'body' => $e->getMessage(),
                'json' => [],
            ];
        }
    }
}
