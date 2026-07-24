<?php

namespace App\Services\Telegram\Handlers;

use App\Models\User;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramKeyboardFactory;
use App\Services\Telegram\TelegramStateService;
use App\Services\Telegram\Handlers\WorkoutFlowHandler;
use App\Services\Telegram\Handlers\WorkoutSetInputHandler;

class CallbackQueryHandler
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
        private readonly TelegramStateService $stateService,
        private readonly WorkoutFlowHandler $workoutFlowHandler,
        private readonly WorkoutSetInputHandler $workoutSetInputHandler,
        private readonly GoalsHandler $goalsHandler,
        private readonly HistoryHandler $historyHandler,
        private readonly StatisticsHandler $statisticsHandler,
        private readonly RecordsHandler $recordsHandler,
    ) {
    }

    public function handle(User $user, array $callbackQuery, int $updateId): void
    {
        $callbackQueryId = (string) data_get($callbackQuery, 'id');
        $data = (string) data_get($callbackQuery, 'data', '');
        $chatId = (int) data_get($callbackQuery, 'message.chat.id');
        $messageId = (int) data_get($callbackQuery, 'message.message_id');

        if ($data === 'common:cancel') {
            $this->stateService->forget($user);
            $this->bot->answerCallbackQuery($callbackQueryId, __('telegram.cancelled'));
            $this->bot->editMessageText($chatId, $messageId, __('telegram.main_menu_title'), [
                'reply_markup' => $this->keyboards->mainMenu(),
            ]);

            return;
        }

        if ($data === 'common:menu') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->bot->editMessageText($chatId, $messageId, __('telegram.main_menu_title'), [
                'reply_markup' => $this->keyboards->mainMenu(),
            ]);

            return;
        }

        $segments = explode(':', $data);
        $scope = $segments[0] ?? '';
        $action = $segments[1] ?? '';
        $target = $segments[2] ?? null;
        $tail = $segments[3] ?? null;

        match ($scope) {
            'workout' => $this->handleWorkoutCallbacks($callbackQueryId, $user, $chatId, $messageId, $action, $target),
            'set' => $this->handleSetCallbacks($callbackQueryId, $user, $chatId, $messageId, $action, $target, $tail),
            'exercise' => $this->handleExerciseCallbacks($callbackQueryId, $user, $chatId, $messageId, $action, $target),
            'goals' => $this->handleGoalsCallbacks($callbackQueryId, $user, $chatId, $messageId, $action, $target),
            'history' => $this->handleHistoryCallbacks($callbackQueryId, $user, $chatId, $messageId, $action, $target),
            'stats' => $this->handleStatsCallbacks($callbackQueryId, $user, $chatId, $messageId, $action),
            'records' => $this->handleRecordsCallbacks($callbackQueryId, $user, $chatId, $messageId, $action),
            'templates', 'settings' => $this->handlePlaceholderSection($callbackQueryId, $chatId, $messageId, $scope, $action),
            default => $this->bot->answerCallbackQuery($callbackQueryId, __('telegram.unknown_action')),
        };
    }

    private function handleWorkoutCallbacks(string $callbackQueryId, User $user, int $chatId, int $messageId, string $action, ?string $target): void
    {
        if ($action === 'start') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->workoutFlowHandler->showTemplates($user, $chatId, $messageId);

            return;
        }

        if ($action === 'template') {
            $this->bot->answerCallbackQuery($callbackQueryId);

            if ($target === 'empty') {
                $this->workoutFlowHandler->startWorkout($user, $chatId, $messageId, null);

                return;
            }

            $this->workoutFlowHandler->startWorkout($user, $chatId, $messageId, $target !== null ? (int) $target : null);

            return;
        }

        if ($action === 'exercise') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->workoutFlowHandler->showExercise($user, $chatId, $messageId, (int) $target);

            return;
        }

        if ($action === 'complete') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->workoutFlowHandler->completeWorkout($user, $chatId, $messageId);

            return;
        }

        if ($action === 'back') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->workoutFlowHandler->backToWorkout($user, $chatId, $messageId);

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, __('telegram.unknown_action'));
    }

    private function handleSetCallbacks(string $callbackQueryId, User $user, int $chatId, int $messageId, string $action, ?string $target, ?string $tail): void
    {
        if ($action === 'add') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->workoutFlowHandler->beginSetInput($user, $chatId, $messageId, (int) $target, false);

            return;
        }

        if ($action === 'repeat') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->workoutFlowHandler->beginSetInput($user, $chatId, $messageId, (int) $target, true);

            return;
        }

        if ($action === 'rpe' && $target === 'skip') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->workoutSetInputHandler->completeSkippedRpe($user, $chatId, $messageId);

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, __('telegram.unknown_action'));
    }

    private function handleExerciseCallbacks(string $callbackQueryId, User $user, int $chatId, int $messageId, string $action, ?string $target): void
    {
        if ($action === 'back') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->workoutFlowHandler->backToWorkout($user, $chatId, $messageId);

            return;
        }

        if ($action === 'progress' && $target !== null) {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->workoutFlowHandler->showExerciseForecast($user, $chatId, $messageId, (int) $target);

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, __('telegram.unknown_action'));
    }

    private function handleHistoryCallbacks(string $callbackQueryId, User $user, int $chatId, int $messageId, string $action, ?string $target): void
    {
        if ($action === 'list') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->historyHandler->showHistory($user, $chatId, $messageId);

            return;
        }

        if ($action === 'open' && $target !== null) {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->historyHandler->showWorkout($user, $chatId, $messageId, (int) $target);

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, __('telegram.unknown_action'));
    }

    private function handleStatsCallbacks(string $callbackQueryId, User $user, int $chatId, int $messageId, string $action): void
    {
        if ($action === 'summary') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->statisticsHandler->showSummary($user, $chatId, $messageId);

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, __('telegram.unknown_action'));
    }

    private function handleRecordsCallbacks(string $callbackQueryId, User $user, int $chatId, int $messageId, string $action): void
    {
        if ($action === 'list') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->recordsHandler->showRecords($user, $chatId, $messageId);

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, __('telegram.unknown_action'));
    }

    private function handleGoalsCallbacks(string $callbackQueryId, User $user, int $chatId, int $messageId, string $action, ?string $target): void
    {
        if ($action === 'list') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->goalsHandler->showGoals($user, $chatId, $messageId);

            return;
        }

        if ($action === 'create') {
            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->goalsHandler->showTypeSelection($user, $chatId, $messageId);

            return;
        }

        if ($action === 'type' && $target !== null) {
            $type = \App\Enums\UserGoalType::tryFrom($target);

            if ($type === null) {
                $this->bot->answerCallbackQuery($callbackQueryId, __('telegram.unknown_action'));

                return;
            }

            $this->bot->answerCallbackQuery($callbackQueryId);
            $this->goalsHandler->selectType($user, $chatId, $messageId, $type);

            return;
        }

        $this->bot->answerCallbackQuery($callbackQueryId, __('telegram.unknown_action'));
    }

    private function handlePlaceholderSection(string $callbackQueryId, int $chatId, int $messageId, string $scope, string $action): void
    {
        $message = __("telegram.sections.{$scope}.{$action}");

        if ($message === "telegram.sections.{$scope}.{$action}") {
            $message = __('telegram.section_in_development');
        }

        $this->bot->answerCallbackQuery($callbackQueryId, $message);
        $this->bot->editMessageText($chatId, $messageId, $message, [
            'reply_markup' => $this->keyboards->mainMenu(),
        ]);
    }
}
