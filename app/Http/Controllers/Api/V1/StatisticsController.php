<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StatisticsSummaryResource;
use App\Models\Exercise;
use App\Services\Forecasting\ExerciseProgressForecastService;
use App\Services\Statistics\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsService $statistics,
        private readonly ExerciseProgressForecastService $forecast,
    ) {
    }

    public function summary(Request $request): StatisticsSummaryResource
    {
        return new StatisticsSummaryResource($this->statistics->summary($request->user()));
    }

    public function exercise(Request $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('view', $exercise);

        $user = $request->user();

        $workoutSets = $exercise->workoutExercises()
            ->whereHas('workout', fn ($query) => $query->where('user_id', $user->id)->where('status', 'completed'))
            ->with('workout')
            ->get()
            ->flatMap(fn ($workoutExercise) => $workoutExercise->sets);

        $summary = [
            'exercise_id' => $exercise->id,
            'exercise_name' => $exercise->name,
            'last_working_weight' => $workoutSets->sortByDesc('completed_at')->first()?->weight,
            'max_weight' => $workoutSets->max('weight'),
            'max_repetitions' => $workoutSets->max('repetitions'),
            'max_volume' => $workoutSets->max(fn ($set) => (float) $set->weight * (int) $set->repetitions),
            'total_sets' => $workoutSets->count(),
            'average_rpe' => $workoutSets->whereNotNull('rpe')->avg('rpe'),
            'last_workout_at' => $workoutSets->sortByDesc('completed_at')->first()?->completed_at?->toISOString(),
            'current_streak_weeks' => $this->statistics->summary($user)['streak_weeks'],
        ];

        return response()->json([
            'data' => $summary,
        ]);
    }

    public function forecast(Request $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('view', $exercise);

        return response()->json([
            'data' => $this->forecast->forecast($request->user(), $exercise),
        ]);
    }
}
