<?php

namespace App\Services\Telegram\Handlers;

use App\Models\User;
use App\Services\Statistics\StatisticsService;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramKeyboardFactory;

class StatisticsHandler
{
    public function __construct(
        private readonly StatisticsService $statistics,
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
    ) {
    }

    public function showSummary(User $user, int $chatId, ?int $messageId = null): void
    {
        $summary = $this->statistics->summary($user);

        $text = implode("\n", [
            __('telegram.stats.title'),
            '',
            __('telegram.stats.week_workouts', ['count' => $summary['week_workouts']]),
            __('telegram.stats.month_workouts', ['count' => $summary['month_workouts']]),
            __('telegram.stats.total_duration', ['duration' => $this->formatDuration($summary['total_duration_seconds'])]),
            __('telegram.stats.total_sets', ['count' => $summary['total_sets']]),
            __('telegram.stats.total_volume', ['volume' => number_format((float) $summary['total_volume'], 1, '.', ' ')]),
            __('telegram.stats.records_this_month', ['count' => $summary['records_this_month']]),
            __('telegram.stats.streak_weeks', ['count' => $summary['streak_weeks']]),
            $summary['volume_change_percent'] !== null
                ? __('telegram.stats.volume_change', ['percent' => number_format((float) $summary['volume_change_percent'], 1, '.', ' ')])
                : __('telegram.stats.volume_change_unknown'),
            '',
            __('telegram.stats.top_exercises'),
            $this->formatTopItems($summary['top_exercises']),
            '',
            __('telegram.stats.muscle_groups'),
            $this->formatTopItems($summary['muscle_groups']),
        ]);

        $this->send($chatId, $messageId, $text);
    }

    private function send(int $chatId, ?int $messageId, string $text): void
    {
        $markup = ['reply_markup' => $this->keyboards->mainMenu()];

        if ($messageId !== null) {
            $this->bot->editMessageText($chatId, $messageId, $text, $markup);

            return;
        }

        $this->bot->sendMessage($chatId, $text, $markup);
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0 ? sprintf('%d ч %d мин', $hours, $minutes) : sprintf('%d мин', $minutes);
    }

    private function formatTopItems(array $items): string
    {
        if ($items === []) {
            return '—';
        }

        $lines = [];

        foreach ($items as $label => $count) {
            $lines[] = sprintf('%s — %d', $label, $count);
        }

        return implode("\n", $lines);
    }
}
