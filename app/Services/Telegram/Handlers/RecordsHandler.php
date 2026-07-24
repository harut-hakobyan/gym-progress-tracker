<?php

namespace App\Services\Telegram\Handlers;

use App\Models\User;
use App\Services\Records\PersonalRecordService;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramKeyboardFactory;

class RecordsHandler
{
    public function __construct(
        private readonly PersonalRecordService $records,
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
    ) {
    }

    public function showRecords(User $user, int $chatId, ?int $messageId = null): void
    {
        $records = $this->records->latestForUser($user);

        $lines = [__('telegram.records.title'), ''];

        if ($records->isEmpty()) {
            $lines[] = __('telegram.records.empty');
        } else {
            foreach ($records as $record) {
                $lines[] = __('telegram.records.row', [
                    'exercise' => $record->exercise?->name ?? '—',
                    'type' => __('telegram.records.types.'.$record->type),
                    'value' => number_format((float) $record->value, 1, '.', ' '),
                ]);
            }
        }

        $this->send($chatId, $messageId, implode("\n", $lines));
    }

    private function send(int $chatId, ?int $messageId, string $text): void
    {
        $markup = ['reply_markup' => $this->keyboards->recordsList()];

        if ($messageId !== null) {
            $this->bot->editMessageText($chatId, $messageId, $text, $markup);

            return;
        }

        $this->bot->sendMessage($chatId, $text, $markup);
    }
}
