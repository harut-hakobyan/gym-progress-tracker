<?php

namespace App\Services\Telegram\Handlers;

use App\Enums\TelegramState;
use App\Enums\UserGoalType;
use App\Models\User;
use App\Services\Goals\GoalService;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramKeyboardFactory;
use App\Services\Telegram\TelegramStateService;

class GoalsHandler
{
    public function __construct(
        private readonly GoalService $goals,
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
        private readonly TelegramStateService $stateService,
    ) {
    }

    public function showGoals(User $user, int $chatId, ?int $messageId = null): void
    {
        $goals = $this->goals->activeGoals($user);

        $lines = [__('telegram.goals.title'), ''];

        if ($goals->isEmpty()) {
            $lines[] = __('telegram.goals.empty');
        } else {
            foreach ($goals as $goal) {
                $progress = $this->goals->progress($goal);
                $line = __('telegram.goals.row', [
                    'type' => $this->goals->formatType($goal->type),
                    'target' => $goal->target_value,
                    'current' => $progress['current_value'] !== null ? number_format((float) $progress['current_value'], 1, '.', ' ') : '—',
                    'progress' => $progress['progress_percent'] !== null ? $progress['progress_percent'].'%' : '—',
                ]);

                $lines[] = $line;
            }
        }

        $markup = ['reply_markup' => $this->keyboards->goalsList()];

        if ($messageId !== null) {
            $this->bot->editMessageText($chatId, $messageId, implode("\n", $lines), $markup);

            return;
        }

        $this->bot->sendMessage($chatId, implode("\n", $lines), $markup);
    }

    public function startCreate(User $user, int $chatId, int $messageId): void
    {
        $this->stateService->put($user, TelegramState::AwaitingGoalType, []);

        $this->bot->editMessageText($chatId, $messageId, __('telegram.goals.choose_type'), [
            'reply_markup' => $this->keyboards->goalTypeSelection(),
        ]);
    }

    public function selectType(User $user, int $chatId, int $messageId, UserGoalType $type): void
    {
        $this->stateService->put($user, TelegramState::AwaitingGoalValue, [
            'type' => $type->value,
        ]);

        $this->bot->editMessageText($chatId, $messageId, __('telegram.goals.enter_value', [
            'type' => $this->goals->formatType($type),
        ]), [
            'reply_markup' => $this->keyboards->cancelOnly(),
        ]);
    }

    public function showTypeSelection(User $user, int $chatId, int $messageId): void
    {
        $this->startCreate($user, $chatId, $messageId);
    }
}
