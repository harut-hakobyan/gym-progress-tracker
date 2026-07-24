<?php

namespace App\Services\Telegram\Handlers;

use App\Models\User;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramKeyboardFactory;

class StartCommandHandler
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
    ) {
    }

    public function handle(User $user, int $chatId): void
    {
        $this->bot->sendMessage(
            $chatId,
            __('telegram.welcome', ['name' => $user->name])."\n\n".__('telegram.main_menu_title'),
            ['reply_markup' => $this->keyboards->mainMenu()]
        );
    }
}
