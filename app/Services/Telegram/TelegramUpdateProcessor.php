<?php

namespace App\Services\Telegram;

use App\Models\User;
use App\Services\Telegram\Handlers\TelegramStateMessageHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Telegram\Handlers\CallbackQueryHandler;
use App\Services\Telegram\Handlers\CommandHandler;

class TelegramUpdateProcessor
{
    public function __construct(
        private readonly TelegramUserService $userService,
        private readonly TelegramBotService $bot,
        private readonly CommandHandler $commandHandler,
        private readonly CallbackQueryHandler $callbackQueryHandler,
        private readonly TelegramStateService $stateService,
        private readonly TelegramStateMessageHandler $stateMessageHandler,
    ) {
    }

    public function process(array $payload): void
    {
        $updateId = (int) data_get($payload, 'update_id', 0);

        if ($updateId <= 0) {
            return;
        }

        if (! $this->markAsProcessing($updateId)) {
            Log::channel('telegram')->info('telegram.update.duplicate', ['update_id' => $updateId]);

            return;
        }

        try {
            if (isset($payload['message'])) {
                $this->handleMessage($payload['message'], $updateId);
            } elseif (isset($payload['callback_query'])) {
                $this->handleCallbackQuery($payload['callback_query'], $updateId);
            }

            $this->markAsProcessed($updateId);
        } catch (\Throwable $e) {
            $this->markAsFailed($updateId, $e->getMessage());

            throw $e;
        }
    }

    private function handleMessage(array $message, int $updateId): void
    {
        $user = $this->userService->resolveFromMessage($message);
        app()->setLocale($this->userService->localeForUser($user));
        $text = trim((string) data_get($message, 'text', ''));
        $chatId = (int) data_get($message, 'chat.id');
        $messageId = (int) data_get($message, 'message_id', 0);
        $state = $this->stateService->get($user);
        $shouldDelete = $messageId > 0;

        if ($state !== null && $text === '') {
            $this->stateMessageHandler->handle($user, $message, $state);
            $this->deleteIncomingMessage($chatId, $messageId);

            return;
        }

        if ($text === '') {
            $this->deleteIncomingMessage($chatId, $messageId);

            return;
        }

        if (! str_starts_with($text, '/')) {
            if ($state !== null) {
                $this->stateMessageHandler->handle($user, $message, $state);
                $this->deleteIncomingMessage($chatId, $messageId);

                return;
            }
        }

        $this->commandHandler->handle($user, $message, $text, $updateId);
        if ($shouldDelete) {
            $this->deleteIncomingMessage($chatId, $messageId);
        }
    }

    private function handleCallbackQuery(array $callbackQuery, int $updateId): void
    {
        $user = $this->userService->resolveFromCallbackQuery($callbackQuery);

        if (! $user instanceof User) {
            return;
        }

        app()->setLocale($this->userService->localeForUser($user));
        $this->callbackQueryHandler->handle($user, $callbackQuery, $updateId);
    }

    private function markAsProcessing(int $updateId): bool
    {
        $updated = DB::table('processed_telegram_updates')
            ->where('update_id', $updateId)
            ->whereIn('status', ['received', 'failed'])
            ->update([
                'status' => 'processing',
                'attempts' => DB::raw('attempts + 1'),
                'processing_started_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated === 1) {
            return true;
        }

        return false;
    }

    private function markAsProcessed(int $updateId): void
    {
        DB::table('processed_telegram_updates')
            ->where('update_id', $updateId)
            ->update([
                'status' => 'processed',
                'processed_at' => now(),
                'updated_at' => now(),
                'last_error' => null,
            ]);
    }

    private function markAsFailed(int $updateId, string $message): void
    {
        DB::table('processed_telegram_updates')
            ->where('update_id', $updateId)
            ->update([
                'status' => 'failed',
                'last_error' => mb_substr($message, 0, 2000),
                'updated_at' => now(),
            ]);

        Log::channel('telegram')->error('telegram.update.exception', [
            'update_id' => $updateId,
            'message' => $message,
        ]);
    }

    private function deleteIncomingMessage(int $chatId, int $messageId): void
    {
        if ($chatId <= 0 || $messageId <= 0) {
            return;
        }

        $this->bot->deleteMessage($chatId, $messageId);
    }
}
