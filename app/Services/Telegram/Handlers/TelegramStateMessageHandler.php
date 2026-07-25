<?php

namespace App\Services\Telegram\Handlers;

use App\Enums\TelegramState;
use App\Models\User;
use App\Models\UserTelegramState;

class TelegramStateMessageHandler
{
    public function __construct(
        private readonly TemplateFlowHandler $templateFlowHandler,
        private readonly WorkoutSetInputHandler $workoutSetInputHandler,
        private readonly GoalsInputHandler $goalsInputHandler,
    ) {
    }

    public function handle(User $user, array $message, UserTelegramState $state): void
    {
        match ($state->state) {
            TelegramState::AwaitingTemplateName->value,
            TelegramState::AwaitingTemplateMuscleGroups->value => $this->templateFlowHandler->handle($user, $message, $state),
            TelegramState::AwaitingSetWeight->value,
            TelegramState::AwaitingSetRepetitions->value => $this->workoutSetInputHandler->handle($user, $message, $state),
            TelegramState::AwaitingGoalValue->value,
            TelegramState::AwaitingGoalDate->value => $this->goalsInputHandler->handle($user, $message, $state),
            default => null,
        };
    }
}
