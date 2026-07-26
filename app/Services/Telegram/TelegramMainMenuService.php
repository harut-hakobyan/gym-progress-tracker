<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TelegramMainMenuService
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
        private readonly TelegramAccessService $access,
    ) {
    }

    public function show(User $user, int $chatId, string $text, ?int $messageId = null, bool $sendAsNewMessage = false): void
    {
        $replyMarkup = [
            'reply_markup' => $this->keyboards->mainMenu($this->access->isAdmin($user)),
        ];

        if (! $sendAsNewMessage) {
            $targetMessageId = $messageId ?? $this->getActiveMessageId($user);

            if ($targetMessageId !== null && $targetMessageId > 0) {
                $response = $this->bot->editMessageTextResult($chatId, $targetMessageId, $text, $replyMarkup);

                if ($response['successful']) {
                    $this->rememberActiveMessageId($user, $targetMessageId);

                    return;
                }

                if ($this->isMessageNotModified($response['body'])) {
                    $this->rememberActiveMessageId($user, $targetMessageId);

                    return;
                }
            }
        }

        $activeMessageId = $this->getActiveMessageId($user);

        if ($sendAsNewMessage && $activeMessageId !== null && $activeMessageId > 0) {
            $this->bot->deleteMessage($chatId, $activeMessageId);
        }

        $response = $this->bot->sendMessageResult($chatId, $text, $replyMarkup);
        $messageId = (int) data_get($response, 'json.result.message_id', 0);

        if ($response['successful'] && $messageId > 0) {
            $this->rememberActiveMessageId($user, $messageId);
        }
    }

    private function getActiveMessageId(User $user): ?int
    {
        $messageId = Cache::get($this->cacheKey($user));

        if (! is_numeric($messageId)) {
            return null;
        }

        $messageId = (int) $messageId;

        return $messageId > 0 ? $messageId : null;
    }

    private function rememberActiveMessageId(User $user, int $messageId): void
    {
        Cache::put($this->cacheKey($user), $messageId, now()->addDays(30));
    }

    private function cacheKey(User $user): string
    {
        return 'telegram:main_menu_message_id:'.$user->id;
    }

    private function isMessageNotModified(string $body): bool
    {
        return str_contains($body, 'message is not modified');
    }
}
