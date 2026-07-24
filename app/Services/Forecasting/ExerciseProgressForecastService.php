<?php

namespace App\Services\Forecasting;

use App\Enums\WorkoutSetType;
use App\Enums\WorkoutStatus;
use App\Models\Exercise;
use App\Models\User;
use App\Models\WorkoutSet;
use App\Services\Workouts\WorkoutMetricsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExerciseProgressForecastService
{
    public function __construct(
        private readonly WorkoutMetricsService $metrics,
    ) {
    }

    public function forecast(User $user, Exercise $exercise, ?float $targetWeight = null): ?array
    {
        $samples = $this->exerciseSamples($user, $exercise);

        if ($samples->count() > 12) {
            $samples = $samples->slice(-12)->values();
        }

        if ($samples->count() < 3) {
            return null;
        }

        $filtered = $this->filterOutliers($samples);

        if ($filtered->count() < 3) {
            $filtered = $samples;
        }

        $regression = $this->linearRegression($filtered);

        $latest = $samples->last();
        $currentDate = Carbon::parse($latest['date'])->startOfDay();
        $originDate = Carbon::parse($filtered->first()['date'])->startOfDay();
        $currentX = $originDate->diffInDays($currentDate);

        $forecast = [];

        foreach ([30, 60, 90] as $days) {
            $predictedWeight = $this->metrics->roundWeight(max(
                0.0,
                $regression['intercept'] + ($regression['slope'] * ($currentX + $days))
            ));

            $forecast[$days] = [
                'weight' => $predictedWeight,
                'repetitions' => $latest['repetitions'],
            ];
        }

        $currentOneRepMax = $this->metrics->estimatedOneRepMax($latest['weight'], $latest['repetitions']);
        $confidence = $this->confidence($filtered, $regression['r2']);
        $targetDate = $this->estimateTargetDate($regression, $currentX, $targetWeight);

        return [
            'current_weight' => $latest['weight'],
            'current_repetitions' => $latest['repetitions'],
            'current_one_rep_max' => $currentOneRepMax,
            'forecasts' => $forecast,
            'confidence' => $confidence,
            'target_date' => $targetDate?->toDateString(),
            'slope_per_day' => $regression['slope'],
            'sample_count' => $filtered->count(),
        ];
    }

    private function exerciseSamples(User $user, Exercise $exercise): Collection
    {
        $rows = DB::table('workout_sets')
            ->join('workout_exercises', 'workout_exercises.id', '=', 'workout_sets.workout_exercise_id')
            ->join('workouts', 'workouts.id', '=', 'workout_exercises.workout_id')
            ->where('workouts.user_id', $user->id)
            ->where('workouts.status', WorkoutStatus::Completed->value)
            ->where('workout_exercises.exercise_id', $exercise->id)
            ->where('workout_sets.is_completed', true)
            ->where('workout_sets.type', WorkoutSetType::Working->value)
            ->orderBy('workouts.completed_at')
            ->orderBy('workout_sets.set_number')
            ->get([
                'workouts.id as workout_id',
                'workouts.completed_at as completed_at',
                'workout_sets.weight',
                'workout_sets.repetitions',
                'workout_sets.set_number',
            ]);

        return $rows
            ->groupBy('workout_id')
            ->map(function (Collection $group): array {
                $sample = $group
                    ->sortByDesc(fn ($row): string => sprintf('%012.2f-%06d', (float) $row->weight, (int) $row->set_number))
                    ->first();

                return [
                    'date' => Carbon::parse($sample->completed_at)->startOfDay(),
                    'weight' => (float) $sample->weight,
                    'repetitions' => (int) $sample->repetitions,
                ];
            })
            ->sortBy('date')
            ->values();
    }

    private function filterOutliers(Collection $samples): Collection
    {
        $weights = $samples->pluck('weight')->all();
        $median = $this->median($weights);
        $deviations = array_map(
            static fn (float $weight): float => abs($weight - $median),
            $weights
        );

        $mad = $this->median($deviations);
        $threshold = max(5.0, $mad * 3.5);

        $filtered = $samples->filter(
            static fn (array $sample): bool => abs($sample['weight'] - $median) <= $threshold
        )->values();

        return $filtered;
    }

    private function linearRegression(Collection $samples): array
    {
        $origin = Carbon::parse($samples->first()['date'])->startOfDay();
        $points = $samples->map(function (array $sample) use ($origin): array {
            return [
                'x' => $origin->diffInDays(Carbon::parse($sample['date'])->startOfDay()),
                'y' => (float) $sample['weight'],
            ];
        })->values();

        $count = $points->count();
        $sumX = $points->sum('x');
        $sumY = $points->sum('y');
        $sumXX = $points->sum(fn (array $point): float => $point['x'] * $point['x']);
        $sumXY = $points->sum(fn (array $point): float => $point['x'] * $point['y']);
        $denominator = ($count * $sumXX) - ($sumX * $sumX);

        $slope = $denominator !== 0.0
            ? (($count * $sumXY) - ($sumX * $sumY)) / $denominator
            : 0.0;

        $intercept = $count > 0 ? (($sumY - ($slope * $sumX)) / $count) : 0.0;
        $meanY = $count > 0 ? $sumY / $count : 0.0;

        $ssTot = $points->sum(fn (array $point): float => ($point['y'] - $meanY) ** 2);
        $ssRes = $points->sum(function (array $point) use ($slope, $intercept): float {
            $predicted = $intercept + ($slope * $point['x']);

            return ($point['y'] - $predicted) ** 2;
        });

        $r2 = $ssTot > 0 ? max(0.0, min(1.0, 1 - ($ssRes / $ssTot))) : 0.0;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r2' => $r2,
        ];
    }

    private function confidence(Collection $samples, float $r2): float
    {
        $countScore = min($samples->count(), 12) / 12;
        $gapScore = $this->regularityScore($samples);

        return round(max(0.1, min(0.95, 0.25 + ($countScore * 0.35) + ($r2 * 0.25) + ($gapScore * 0.15))), 2);
    }

    private function regularityScore(Collection $samples): float
    {
        if ($samples->count() < 2) {
            return 0.0;
        }

        $dates = $samples->pluck('date')->map(fn ($date): Carbon => Carbon::parse($date)->startOfDay())->values();
        $gaps = [];

        for ($index = 1; $index < $dates->count(); $index++) {
            $gaps[] = $dates[$index - 1]->diffInDays($dates[$index]);
        }

        if ($gaps === []) {
            return 0.0;
        }

        $averageGap = array_sum($gaps) / count($gaps);

        return match (true) {
            $averageGap <= 7 => 1.0,
            $averageGap <= 14 => 0.8,
            $averageGap <= 21 => 0.6,
            $averageGap <= 30 => 0.4,
            default => 0.2,
        };
    }

    private function estimateTargetDate(array $regression, int $currentX, ?float $targetWeight): ?Carbon
    {
        if ($targetWeight === null) {
            return null;
        }

        $slope = (float) $regression['slope'];

        if ($slope <= 0.0) {
            return null;
        }

        $currentValue = (float) $regression['intercept'] + ($slope * $currentX);
        $daysToTarget = (int) ceil(($targetWeight - $currentValue) / $slope);

        if ($daysToTarget < 0) {
            return now();
        }

        return now()->addDays($daysToTarget);
    }

    private function median(array $values): float
    {
        sort($values);

        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }
}
