<?php

namespace App\Services\Telegram\Handlers;

use App\Enums\TelegramState;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Services\Forecasting\ExerciseProgressForecastService;
use App\Services\Telegram\Handlers\WorkoutSetInputHandler;
use App\Services\Telegram\TelegramAccessService;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramKeyboardFactory;
use App\Services\Telegram\TelegramStateService;
use App\Services\Workouts\WorkoutFlowService;
use App\Services\Workouts\WorkoutMetricsService;

class WorkoutFlowHandler
{
    public function __construct(
        private readonly WorkoutFlowService $workouts,
        private readonly WorkoutMetricsService $metrics,
        private readonly ExerciseProgressForecastService $forecasting,
        private readonly WorkoutSetInputHandler $workoutSetInputHandler,
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
        private readonly TelegramStateService $stateService,
        private readonly TelegramAccessService $access,
    ) {
    }

    public function showTemplates(User $user, int $chatId, ?int $messageId = null, string $view = 'custom'): void
    {
        $isStandardView = $view === 'standard';

        $templates = $isStandardView
            ? $this->workouts->standardTemplates()
            : $this->workouts->customTemplates($user);

        $templates = $templates
            ->map(fn ($template) => ['id' => $template->id, 'name' => $template->name])
            ->all();

        $text = $isStandardView
            ? __('telegram.workout.standard_templates_heading')
            : __('telegram.workout.choose_template');

        if ($templates === []) {
            $text .= $isStandardView
                ? "\n\n".__('telegram.workout.no_standard_templates')
                : "\n\n".__('telegram.workout.no_custom_templates');
        }

        $replyMarkup = ['reply_markup' => $isStandardView
            ? $this->keyboards->workoutStandardTemplates($templates)
            : $this->keyboards->workoutTemplates($templates)];

        if ($messageId !== null) {
            $this->bot->editMessageText($chatId, $messageId, $text, $replyMarkup);

            return;
        }

        $this->bot->sendMessage($chatId, $text, $replyMarkup);
    }

    public function startWorkout(User $user, int $chatId, int $messageId, ?int $templateId): void
    {
        $template = $templateId !== null ? $this->workouts->templateForUser($user, $templateId) : null;
        $workout = $this->workouts->startWorkout($user, $template);

        $this->showWorkoutDashboard($user, $chatId, $messageId, $workout, __('telegram.workout.started'));
    }

    public function showWorkoutDashboard(User $user, int $chatId, int $messageId, ?Workout $workout = null, ?string $headline = null): void
    {
        $workout ??= $this->workouts->activeWorkout($user);

        if ($workout === null) {
            $this->showTemplates($user, $chatId, $messageId);

            return;
        }

        $workout->loadMissing(['workoutExercises.exercise', 'workoutExercises.sets']);

        $exercises = $this->workouts->availableExercises($user)
            ->map(fn ($exercise) => ['id' => $exercise->id, 'name' => $exercise->name])
            ->all();

        $text = $this->buildWorkoutDashboardText($workout, $headline);

        $this->bot->editMessageText($chatId, $messageId, $text, [
            'reply_markup' => $this->keyboards->exerciseSelection($exercises),
        ]);
    }

    public function showExercise(User $user, int $chatId, int $messageId, int $exerciseId): void
    {
        $workout = $this->workouts->activeWorkout($user);

        if ($workout === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.workout.no_active_workout'), [
                'reply_markup' => $this->keyboards->mainMenu($this->access->isAdmin($user)),
            ]);

            return;
        }

        $exercise = $this->workouts->exerciseForUser($user, $exerciseId);

        if ($exercise === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.workout.exercise_not_found'), [
                'reply_markup' => $this->keyboards->mainMenu($this->access->isAdmin($user)),
            ]);

            return;
        }

        $workoutExercise = $this->workouts->attachExercise($workout, $exercise);
        $overview = $this->workouts->exerciseOverview($user, $exercise);

        $text = $this->buildExerciseText($exercise->name, $overview);

        $this->bot->editMessageText($chatId, $messageId, $text, [
            'reply_markup' => $this->keyboards->workoutExerciseActions($workoutExercise->id, $exercise->id, $overview['last_set'] !== null),
        ]);
    }

    public function showExerciseForecast(User $user, int $chatId, int $messageId, int $exerciseId): void
    {
        $exercise = $this->workouts->exerciseForUser($user, $exerciseId);

        if ($exercise === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.workout.exercise_not_found'), [
                'reply_markup' => $this->keyboards->mainMenu($this->access->isAdmin($user)),
            ]);

            return;
        }

        $forecast = $this->forecasting->forecast($user, $exercise);

        if ($forecast === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.forecast.insufficient_data', [
                'exercise' => $exercise->name,
            ]), [
                'reply_markup' => $this->keyboards->exerciseForecastActions(),
            ]);

            return;
        }

        $this->bot->editMessageText(
            $chatId,
            $messageId,
            $this->buildForecastText($exercise->name, $forecast),
            [
                'reply_markup' => $this->keyboards->exerciseForecastActions(),
            ]
        );
    }

    public function beginSetInput(User $user, int $chatId, int $messageId, int $workoutExerciseId, bool $repeat = false): void
    {
        $workoutExercise = $this->workouts->workoutExerciseById($user, $workoutExerciseId);

        if ($workoutExercise === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.workout.exercise_not_found'), [
                'reply_markup' => $this->keyboards->mainMenu($this->access->isAdmin($user)),
            ]);

            return;
        }

        $lastSet = $repeat ? $this->workouts->lastSet($workoutExercise) : null;

        if ($repeat && $lastSet !== null) {
            $this->workoutSetInputHandler->saveSetAndRespond($user, $chatId, $messageId, [
                'workout_exercise_id' => $workoutExercise->id,
                'weight' => (float) $lastSet->weight,
                'repetitions' => (int) $lastSet->repetitions,
            ]);

            return;
        }

        $payload = [
            'workout_exercise_id' => $workoutExercise->id,
            'repeat' => $repeat,
            'prefill' => $lastSet?->toArray(),
        ];

        $this->stateService->put($user, TelegramState::AwaitingSetWeight, $payload);

        $prompt = __('telegram.workout.set_weight_prompt');

        if ($lastSet !== null) {
            $prompt .= "\n\n".__('telegram.workout.repeat_hint', [
                'weight' => $lastSet->weight,
                'repetitions' => $lastSet->repetitions,

            ]);
        }

        $this->bot->editMessageText($chatId, $messageId, $prompt, [
            'reply_markup' => $this->keyboards->cancelOnly(),
        ]);
    }

    public function completeWorkout(User $user, int $chatId, int $messageId): void
    {
        $workout = $this->workouts->activeWorkout($user);

        if ($workout === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.workout.no_active_workout'), [
                'reply_markup' => $this->keyboards->mainMenu($this->access->isAdmin($user)),
            ]);

            return;
        }

        $summary = $this->workouts->completeWorkout($workout);

        $text = __('telegram.workout.completed', [
            'duration' => $this->formatDuration((int) $summary['duration_seconds']),
            'exercise_count' => $summary['exercise_count'],
            'set_count' => $summary['set_count'],
            'volume' => number_format($summary['volume'], 1, '.', ' '),
        ]);

        $this->bot->editMessageText($chatId, $messageId, $text, [
            'reply_markup' => $this->keyboards->mainMenu($this->access->isAdmin($user)),
        ]);
    }

    public function backToWorkout(User $user, int $chatId, int $messageId): void
    {
        $workout = $this->workouts->activeWorkout($user);

        if ($workout === null) {
            $this->showTemplates($user, $chatId, $messageId);

            return;
        }

        $this->showWorkoutDashboard($user, $chatId, $messageId, $workout, __('telegram.workout.choose_exercise'));
    }

    private function buildWorkoutDashboardText(Workout $workout, ?string $headline = null): string
    {
        $parts = [];

        if ($headline !== null) {
            $parts[] = $headline;
            $parts[] = '';
        }

        $parts[] = __('telegram.workout.active_workout');
        $parts[] = $workout->name;
        $parts[] = '';
        $parts[] = __('telegram.workout.exercises_count', ['count' => $workout->workoutExercises->count()]);
        $parts[] = __('telegram.workout.sets_count', [
            'count' => $workout->workoutExercises->sum(fn (WorkoutExercise $exercise) => $exercise->sets->count()),
        ]);

        return implode("\n", $parts);
    }

    private function buildExerciseText(string $name, array $overview): string
    {
        $lines = [
            $name,
            '',
            __('telegram.workout.last_result'),
        ];

        if ($overview['last_set'] === null) {
            $lines[] = __('telegram.workout.no_history');
        } else {
            $lines[] = __('telegram.workout.last_result_value', [
                'weight' => $overview['last_set']->weight,
                'repetitions' => $overview['last_set']->repetitions,
            ]);
        }

        $lines[] = '';
        $lines[] = '';
        $lines[] = __('telegram.workout.best_weight', [
            'weight' => $overview['best_weight'] !== null ? $overview['best_weight'] : __('telegram.workout.unknown_value'),
        ]);
        $lines[] = __('telegram.workout.best_1rm', [
            'value' => $overview['best_one_rep_max'] !== null ? number_format($overview['best_one_rep_max'], 1, '.', ' ') : __('telegram.workout.unknown_value'),
        ]);
        $lines[] = __('telegram.workout.recommendation', [
            'weight' => number_format((float) $overview['recommended_weight'], 1, '.', ' '),
        ]);
        return implode("\n", $lines);
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return __('telegram.workout.duration_hours_minutes', ['hours' => $hours, 'minutes' => $minutes]);
        }

        return __('telegram.workout.duration_minutes', ['minutes' => $minutes]);
    }

    private function buildForecastText(string $exerciseName, array $forecast): string
    {
        $lines = [
            __('telegram.forecast.title', ['exercise' => $exerciseName]),
            '',
            __('telegram.forecast.current', [
                'weight' => number_format((float) $forecast['current_weight'], 1, '.', ' '),
                'repetitions' => $forecast['current_repetitions'],
                'one_rm' => number_format((float) $forecast['current_one_rep_max'], 1, '.', ' '),
            ]),
            '',
        ];

        foreach ([30, 60, 90] as $days) {
            $lines[] = __('telegram.forecast.in_days', [
                'days' => $days,
                'weight' => number_format((float) $forecast['forecasts'][$days]['weight'], 1, '.', ' '),
                'repetitions' => $forecast['forecasts'][$days]['repetitions'],
            ]);
        }

        $lines[] = '';
        $lines[] = __('telegram.forecast.confidence', [
            'percent' => (int) round(((float) $forecast['confidence']) * 100),
        ]);
        $lines[] = __('telegram.forecast.note');

        return implode("\n", $lines);
    }
}
