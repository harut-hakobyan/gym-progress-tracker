<?php

namespace App\Services\Telegram\Handlers;

use App\Enums\TelegramState;
use App\Enums\UserGoalType;
use App\Models\User;
use App\Models\UserTelegramState;
use App\Services\Goals\GoalService;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramKeyboardFactory;
use App\Services\Telegram\TelegramStateService;
use Carbon\Carbon;

class GoalsInputHandler
{
    public function __construct(
        private readonly GoalService $goals,
        private readonly TelegramStateService $stateService,
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
        private readonly GoalsHandler $goalsHandler,
    ) {
    }

    public function handle(User $user, array $message, UserTelegramState $state): void
    {
        $text = trim((string) data_get($message, 'text', ''));
        $chatId = (int) data_get($message, 'chat.id');

        match ($state->state) {
            TelegramState::AwaitingGoalValue->value => $this->handleValue($user, $chatId, $text, $state),
            TelegramState::AwaitingGoalDate->value => $this->handleDate($user, $chatId, $text, $state),
            default => null,
        };
    }

    private function handleValue(User $user, int $chatId, string $text, UserTelegramState $state): void
    {
        if (! is_numeric($text)) {
            $this->bot->sendMessage($chatId, __('telegram.goals.invalid_value'), [
                'reply_markup' => $this->keyboards->cancelOnly(),
            ]);

            return;
        }

        $targetValue = (float) $text;

        if ($targetValue <= 0 || $targetValue > 10000) {
            $this->bot->sendMessage($chatId, __('telegram.goals.invalid_value'), [
                'reply_markup' => $this->keyboards->cancelOnly(),
            ]);

            return;
        }

        $this->stateService->put($user, TelegramState::AwaitingGoalDate, array_merge($state->payload ?? [], [
            'target_value' => $targetValue,
        ]));

        $this->bot->sendMessage($chatId, __('telegram.goals.enter_date'), [
            'reply_markup' => $this->keyboards->cancelOnly(),
        ]);
    }

    private function handleDate(User $user, int $chatId, string $text, UserTelegramState $state): void
    {
        $targetDate = null;

        if ($text !== '' && ! in_array(mb_strtolower($text), ['skip', '-', 'no'], true)) {
            try {
                $targetDate = Carbon::createFromFormat('Y-m-d', $text)->startOfDay();
            } catch (\Throwable $e) {
                $this->bot->sendMessage($chatId, __('telegram.goals.invalid_date'), [
                    'reply_markup' => $this->keyboards->cancelOnly(),
                ]);

                return;
            }
        }

        $typeValue = (string) data_get($state->payload, 'type');
        $type = UserGoalType::tryFrom($typeValue);

        if ($type === null) {
            $this->stateService->forget($user);
            $this->bot->sendMessage($chatId, __('telegram.unknown_action'));

            return;
        }

        $goal = $this->goals->create(
            $user,
            $type,
            (float) data_get($state->payload, 'target_value', 0),
            $targetDate
        );

        $this->stateService->forget($user);

        $text = __('telegram.goals.created', [
            'type' => $this->goals->formatType($goal->type),
            'value' => $goal->target_value,
            'date' => $goal->target_date?->format('Y-m-d') ?? '—',
        ]);

        $this->bot->sendMessage($chatId, $text, [
            'reply_markup' => $this->keyboards->goalsList(),
        ]);
    }
}
