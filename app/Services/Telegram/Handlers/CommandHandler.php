<?php

namespace App\Services\Telegram\Handlers;

use App\Models\User;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramKeyboardFactory;
use App\Services\Telegram\TelegramStateService;

class CommandHandler
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
        private readonly TelegramStateService $stateService,
        private readonly StartCommandHandler $startCommandHandler,
        private readonly AdminFlowHandler $adminFlowHandler,
        private readonly WorkoutFlowHandler $workoutFlowHandler,
        private readonly HistoryHandler $historyHandler,
        private readonly StatisticsHandler $statisticsHandler,
        private readonly RecordsHandler $recordsHandler,
        private readonly GoalsHandler $goalsHandler,
    ) {
    }

    public function handle(User $user, array $message, string $text, int $updateId): void
    {
        $chatId = (int) data_get($message, 'chat.id');
        $command = $this->normalizeCommand($text);

        match ($command) {
            '/start' => $this->startCommandHandler->handle($user, $chatId),
            '/menu' => $this->showMainMenu($chatId),
            '/workout' => $this->workoutFlowHandler->showTemplates($user, $chatId),
            '/history' => $this->historyHandler->showHistory($user, $chatId),
            '/stats' => $this->statisticsHandler->showSummary($user, $chatId),
            '/records' => $this->recordsHandler->showRecords($user, $chatId),
            '/goals' => $this->goalsHandler->showGoals($user, $chatId),
            '/settings' => $this->adminFlowHandler->showSettingsMenu($user, $chatId),
            '/cancel' => $this->cancel($user, $chatId),
            '/help' => $this->help($chatId),
            default => $this->bot->sendMessage($chatId, __('telegram.unknown_command')),
        };
    }

    private function normalizeCommand(string $text): string
    {
        $command = strtolower(trim(strtok($text, ' ')));

        if ($command === '') {
            return '';
        }

        $botUsername = strtolower((string) config('telegram.bot_username'));

        if ($botUsername !== '' && str_contains($command, '@')) {
            [$baseCommand, $username] = explode('@', $command, 2);

            if ($username === $botUsername) {
                return $baseCommand;
            }
        }

        return $command;
    }

    private function showMainMenu(int $chatId): void
    {
        $this->bot->sendMessage(
            $chatId,
            __('telegram.main_menu_title'),
            ['reply_markup' => $this->keyboards->mainMenu()]
        );
    }

    private function cancel(User $user, int $chatId): void
    {
        $this->stateService->forget($user);

        $this->bot->sendMessage($chatId, __('telegram.cancelled'), [
            'reply_markup' => $this->keyboards->mainMenu(),
        ]);
    }

    private function help(int $chatId): void
    {
        $this->bot->sendMessage($chatId, __('telegram.help'));
    }

    private function showSection(int $chatId, string $message): void
    {
        $this->bot->sendMessage($chatId, $message);
    }
}
