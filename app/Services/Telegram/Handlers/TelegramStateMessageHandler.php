<?php

namespace App\Services\Telegram\Handlers;

use App\Enums\TelegramState;
use App\Models\User;
use App\Models\UserTelegramState;

class TelegramStateMessageHandler
{
    public function __construct(
        private readonly WorkoutSetInputHandler $workoutSetInputHandler,
        private readonly GoalsInputHandler $goalsInputHandler,
    ) {
    }

    public function handle(User $user, array $message, UserTelegramState $state): void
    {
        match ($state->state) {
            TelegramState::AwaitingSetWeight->value,
            TelegramState::AwaitingSetRepetitions->value,
            TelegramState::AwaitingSetRpe->value => $this->workoutSetInputHandler->handle($user, $message, $state),
            TelegramState::AwaitingGoalValue->value,
            TelegramState::AwaitingGoalDate->value => $this->goalsInputHandler->handle($user, $message, $state),
            default => null,
        };
    }
}
