<?php

namespace App\Services\Telegram\Handlers;

use App\Models\User;
use App\Services\Statistics\StatisticsService;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramKeyboardFactory;
use App\Services\Workouts\WorkoutFlowService;

class HistoryHandler
{
    public function __construct(
        private readonly StatisticsService $statistics,
        private readonly WorkoutFlowService $workouts,
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
    ) {
    }

    public function showHistory(User $user, int $chatId, ?int $messageId = null): void
    {
        $workouts = $this->statistics->history($user);

        $items = $workouts->map(function ($workout): array {
            return [
                'id' => $workout->id,
                'label' => sprintf(
                    '%s — %s — %s кг',
                    $workout->completed_at?->format('d.m.Y'),
                    $workout->name,
                    number_format((float) $this->workoutVolume($workout), 1, '.', ' ')
                ),
            ];
        })->all();

        $lines = [__('telegram.history.title'), ''];

        if ($items === []) {
            $lines[] = __('telegram.history.empty');
        } else {
            foreach ($workouts as $workout) {
                $lines[] = __('telegram.history.row', [
                    'date' => $workout->completed_at?->format('d.m.Y H:i'),
                    'name' => $workout->name,
                    'volume' => number_format((float) $this->workoutVolume($workout), 1, '.', ' '),
                ]);
            }
        }

        $this->send($chatId, $messageId, implode("\n", $lines), $items);
    }

    public function showWorkout(User $user, int $chatId, int $messageId, int $workoutId): void
    {
        $workout = $this->workouts->workoutById($user, $workoutId);

        if ($workout === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.history.not_found'), [
                'reply_markup' => $this->keyboards->historyBack(),
            ]);

            return;
        }

        $workout = $this->statistics->workoutDetails($workout);

        $lines = [
            __('telegram.history.detail_title'),
            $workout->completed_at?->format('d.m.Y H:i') ?? '—',
            '',
            __('telegram.history.detail_summary', [
                'duration' => $this->formatDuration((int) ($workout->duration_seconds ?? 0)),
                'exercise_count' => $workout->workoutExercises->count(),
                'set_count' => $workout->workoutExercises->sum(fn ($exercise) => $exercise->sets->count()),
                'volume' => number_format((float) $this->workoutVolume($workout), 1, '.', ' '),
            ]),
        ];

        foreach ($workout->workoutExercises as $exercise) {
            $lines[] = '';
            $lines[] = $exercise->exercise?->name ?? '—';

            foreach ($exercise->sets as $set) {
                $lines[] = __('telegram.history.set_row', [
                    'number' => $set->set_number,
                    'weight' => $set->weight,
                    'repetitions' => $set->repetitions,
                    'rpe' => $set->rpe ?? '—',
                ]);
            }
        }

        $this->bot->editMessageText($chatId, $messageId, implode("\n", $lines), [
            'reply_markup' => $this->keyboards->historyBack(),
        ]);
    }

    private function send(int $chatId, ?int $messageId, string $text, array $workouts): void
    {
        $markup = ['reply_markup' => $this->keyboards->historyList($workouts)];

        if ($messageId !== null) {
            $this->bot->editMessageText($chatId, $messageId, $text, $markup);

            return;
        }

        $this->bot->sendMessage($chatId, $text, $markup);
    }

    private function workoutVolume($workout): float
    {
        return (float) $workout->workoutExercises
            ->flatMap(fn ($exercise) => $exercise->sets)
            ->sum(fn ($set) => round(((float) $set->weight) * (int) $set->repetitions, 2));
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0 ? sprintf('%d ч %d мин', $hours, $minutes) : sprintf('%d мин', $minutes);
    }
}
