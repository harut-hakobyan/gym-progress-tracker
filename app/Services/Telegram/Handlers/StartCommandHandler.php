<?php

namespace App\Services\Telegram\Handlers;

use App\Models\User;
use App\Services\Telegram\TelegramMainMenuService;

class StartCommandHandler
{
    public function __construct(
        private readonly TelegramMainMenuService $mainMenuService,
    ) {
    }

    public function handle(User $user, int $chatId): void
    {
        $this->mainMenuService->show(
            $user,
            $chatId,
            __('telegram.welcome', ['name' => $user->name])."\n\n".__('telegram.main_menu_title'),
            null,
            true
        );
    }
}
