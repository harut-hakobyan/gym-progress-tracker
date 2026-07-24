<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcessTelegramUpdateJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TelegramWebhookController
{
    public function __invoke(Request $request, string $secret): JsonResponse
    {
        $expectedSecret = (string) config('telegram.webhook_secret');

        if ($expectedSecret === '' || ! hash_equals($expectedSecret, $secret)) {
            Log::channel('telegram')->warning('telegram.webhook.invalid_secret', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $telegramSecretToken = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($telegramSecretToken !== '' && ! hash_equals($expectedSecret, $telegramSecretToken)) {
            Log::channel('telegram')->warning('telegram.webhook.invalid_header_secret', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $payload = $request->all();
        $updateId = data_get($payload, 'update_id');

        if (! is_numeric($updateId) || (int) $updateId <= 0) {
            Log::channel('telegram')->warning('telegram.webhook.invalid_payload', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => true]);
        }

        $telegramId = data_get($payload, 'message.from.id', data_get($payload, 'callback_query.from.id'));
        $actionType = isset($payload['callback_query']) ? 'callback_query' : 'message';

        $inserted = DB::table('processed_telegram_updates')->insertOrIgnore([
            'update_id' => $updateId,
            'telegram_id' => $telegramId ? (int) $telegramId : null,
            'action_type' => $actionType,
            'status' => 'received',
            'attempts' => 0,
            'raw_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'received_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted !== 1) {
            Log::channel('telegram')->info('telegram.webhook.duplicate', [
                'update_id' => $updateId,
            ]);

            return response()->json(['ok' => true]);
        }

        ProcessTelegramUpdateJob::dispatch($payload)->onQueue('telegram');

        Log::channel('telegram')->info('telegram.webhook.received', [
            'update_id' => $updateId,
        ]);

        return response()->json(['ok' => true]);
    }
}
