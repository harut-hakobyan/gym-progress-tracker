<?php

namespace App\Services\Goals;

use App\Enums\UserGoalStatus;
use App\Enums\UserGoalType;
use App\Enums\WorkoutSetType;
use App\Enums\WorkoutStatus;
use App\Models\Exercise;
use App\Models\User;
use App\Models\UserGoal;
use App\Models\WorkoutSet;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GoalService
{
    public function activeGoals(User $user): Collection
    {
        return UserGoal::query()
            ->with('exercise')
            ->where('user_id', $user->id)
            ->where('status', UserGoalStatus::Active)
            ->latest()
            ->get();
    }

    public function create(
        User $user,
        UserGoalType $type,
        float $targetValue,
        ?CarbonInterface $targetDate = null,
        ?Exercise $exercise = null,
    ): UserGoal {
        return UserGoal::query()->create([
            'user_id' => $user->id,
            'exercise_id' => $exercise?->id,
            'type' => $type,
            'target_value' => $targetValue,
            'target_date' => $targetDate?->toDateString(),
            'status' => UserGoalStatus::Active,
        ]);
    }

    public function progress(UserGoal $goal): array
    {
        $goal->loadMissing('exercise');

        $currentValue = match ($goal->type) {
            UserGoalType::WeeklyWorkouts => $this->weeklyWorkoutsCount($goal->user),
            UserGoalType::TargetBodyWeight => $this->latestBodyWeight($goal->user),
            UserGoalType::TargetWeight => $goal->exercise !== null ? $this->latestExerciseWeight($goal->user, $goal->exercise) : null,
            UserGoalType::TargetOneRepMax => $goal->exercise !== null ? $this->latestExerciseOneRepMax($goal->user, $goal->exercise) : null,
        };

        $targetValue = (float) $goal->target_value;
        $progressPercent = null;

        if ($currentValue !== null && $targetValue > 0) {
            $progressPercent = match ($goal->type) {
                UserGoalType::WeeklyWorkouts => min(100.0, round(($currentValue / $targetValue) * 100, 1)),
                default => min(100.0, round(($currentValue / $targetValue) * 100, 1)),
            };
        }

        return [
            'current_value' => $currentValue,
            'progress_percent' => $progressPercent,
        ];
    }

    public function formatType(UserGoalType $type): string
    {
        return __("telegram.goals.types.{$type->value}");
    }

    private function weeklyWorkoutsCount(User $user): ?float
    {
        return (float) DB::table('workouts')
            ->where('user_id', $user->id)
            ->where('status', WorkoutStatus::Completed->value)
            ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
    }

    private function latestBodyWeight(User $user): ?float
    {
        $weight = DB::table('workouts')
            ->where('user_id', $user->id)
            ->where('status', WorkoutStatus::Completed->value)
            ->whereNotNull('user_body_weight')
            ->latest('completed_at')
            ->value('user_body_weight');

        return $weight !== null ? (float) $weight : null;
    }

    private function latestExerciseWeight(User $user, Exercise $exercise): ?float
    {
        $weight = WorkoutSet::query()
            ->join('workout_exercises', 'workout_exercises.id', '=', 'workout_sets.workout_exercise_id')
            ->join('workouts', 'workouts.id', '=', 'workout_exercises.workout_id')
            ->where('workouts.user_id', $user->id)
            ->where('workouts.status', WorkoutStatus::Completed->value)
            ->where('workout_exercises.exercise_id', $exercise->id)
            ->where('workout_sets.type', WorkoutSetType::Working->value)
            ->latest('workouts.completed_at')
            ->value('workout_sets.weight');

        return $weight !== null ? (float) $weight : null;
    }

    private function latestExerciseOneRepMax(User $user, Exercise $exercise): ?float
    {
        $set = WorkoutSet::query()
            ->join('workout_exercises', 'workout_exercises.id', '=', 'workout_sets.workout_exercise_id')
            ->join('workouts', 'workouts.id', '=', 'workout_exercises.workout_id')
            ->where('workouts.user_id', $user->id)
            ->where('workouts.status', WorkoutStatus::Completed->value)
            ->where('workout_exercises.exercise_id', $exercise->id)
            ->where('workout_sets.type', WorkoutSetType::Working->value)
            ->orderByDesc('workouts.completed_at')
            ->select('workout_sets.weight', 'workout_sets.repetitions')
            ->first();

        if ($set === null) {
            return null;
        }

        $weight = (float) $set->weight;
        $repetitions = (int) $set->repetitions;

        if ($repetitions <= 1 || $repetitions > 15) {
            return $weight;
        }

        return round($weight * (1 + ($repetitions / 30)), 2);
    }
}
