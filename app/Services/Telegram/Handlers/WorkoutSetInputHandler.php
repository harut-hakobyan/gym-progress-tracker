<?php

namespace App\Services\Telegram\Handlers;

use App\Enums\TelegramState;
use App\Enums\WorkoutSetType;
use App\Models\User;
use App\Models\UserTelegramState;
use App\Models\WorkoutExercise;
use App\Services\Records\PersonalRecordService;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramKeyboardFactory;
use App\Services\Telegram\TelegramStateService;
use App\Services\Workouts\WorkoutFlowService;
use App\Services\Workouts\WorkoutMetricsService;

class WorkoutSetInputHandler
{
    public function __construct(
        private readonly WorkoutFlowService $workouts,
        private readonly WorkoutMetricsService $metrics,
        private readonly PersonalRecordService $records,
        private readonly TelegramStateService $stateService,
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
    ) {
    }

    public function handle(User $user, array $message, UserTelegramState $state): void
    {
        $text = trim((string) data_get($message, 'text', ''));
        $chatId = (int) data_get($message, 'chat.id');

        match ($state->state) {
            TelegramState::AwaitingSetWeight->value => $this->handleWeight($user, $chatId, $text, $state),
            TelegramState::AwaitingSetRepetitions->value => $this->handleRepetitions($user, $chatId, $text, $state),
            default => null,
        };
    }

    public function completeSkippedRpe(User $user, int $chatId, int $messageId): void
    {
        return;
    }

    private function handleWeight(User $user, int $chatId, string $text, UserTelegramState $state): void
    {
        if (! is_numeric($text)) {
            $this->bot->sendMessage($chatId, __('telegram.workout.invalid_weight'), [
                'reply_markup' => $this->keyboards->cancelOnly(),
            ]);

            return;
        }

        $weight = $this->metrics->roundWeight((float) $text);

        if ($weight < 0 || $weight > 1000) {
            $this->bot->sendMessage($chatId, __('telegram.workout.invalid_weight'), [
                'reply_markup' => $this->keyboards->cancelOnly(),
            ]);

            return;
        }

        $payload = array_merge($state->payload ?? [], [
            'weight' => $weight,
        ]);

        $this->stateService->put($user, TelegramState::AwaitingSetRepetitions, $payload);

        $this->bot->sendMessage($chatId, __('telegram.workout.set_repetitions_prompt', ['weight' => $weight]), [
            'reply_markup' => $this->keyboards->cancelOnly(),
        ]);
    }

    private function handleRepetitions(User $user, int $chatId, string $text, UserTelegramState $state): void
    {
        if (! ctype_digit($text)) {
            $this->bot->sendMessage($chatId, __('telegram.workout.invalid_repetitions'), [
                'reply_markup' => $this->keyboards->cancelOnly(),
            ]);

            return;
        }

        $repetitions = (int) $text;

        if ($repetitions < 1 || $repetitions > 1000) {
            $this->bot->sendMessage($chatId, __('telegram.workout.invalid_repetitions'), [
                'reply_markup' => $this->keyboards->cancelOnly(),
            ]);

            return;
        }

        $payload = array_merge($state->payload ?? [], [
            'repetitions' => $repetitions,
        ]);

        $this->saveSetAndRespond($user, $chatId, null, $payload);
    }

    public function saveSetAndRespond(User $user, int $chatId, ?int $messageId, array $payload): void
    {
        $workoutExerciseId = (int) data_get($payload, 'workout_exercise_id');
        $workoutExercise = $this->workouts->workoutExerciseById($user, $workoutExerciseId);

        if ($workoutExercise === null) {
            $this->stateService->forget($user);
            $this->bot->sendMessage($chatId, __('telegram.workout.exercise_not_found'));

            return;
        }

        $weight = (float) data_get($payload, 'weight', 0);
        $repetitions = (int) data_get($payload, 'repetitions', 0);

        if ($repetitions <= 0) {
            $this->stateService->forget($user);
            $this->bot->sendMessage($chatId, __('telegram.workout.invalid_repetitions'));

            return;
        }

        $set = $this->workouts->addSet(
            $workoutExercise,
            $weight,
            $repetitions,
            null,
            null,
            WorkoutSetType::Working
        );

        $newRecords = $this->records->syncFromWorkoutSet($set);

        $this->stateService->forget($user);

        $volume = $this->metrics->setVolume((float) $set->weight, $set->repetitions);
        $oneRepMax = $this->metrics->estimatedOneRepMax((float) $set->weight, $set->repetitions);

        $text = __('telegram.workout.set_saved')."\n\n".
            'Подход №'.$set->set_number."\n".
            'Всего подходов в упражнении: '.$set->set_number."\n".
            "\n".
            __('telegram.workout.weight', ['weight' => $set->weight])."\n".
            __('telegram.workout.repetitions', ['repetitions' => $set->repetitions])."\n".
            __('telegram.workout.volume', ['volume' => number_format($volume, 1, '.', ' ')])."\n".
            __('telegram.workout.one_rep_max', ['value' => number_format($oneRepMax, 1, '.', ' ')]);

        if ($newRecords !== []) {
            $text .= "\n\n".__('telegram.workout.new_records')."\n";

            foreach ($newRecords as $type) {
                $text .= '• '.__('telegram.workout.records.'.$type)."\n";
            }
        }

        if ($messageId !== null) {
            $this->bot->editMessageText($chatId, $messageId, $text, [
                'reply_markup' => $this->keyboards->setResult($workoutExercise->id),
            ]);

            return;
        }

        $this->bot->sendMessage($chatId, $text, [
            'reply_markup' => $this->keyboards->setResult($workoutExercise->id),
        ]);
    }
}
