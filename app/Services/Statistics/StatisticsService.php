<?php

namespace App\Services\Statistics;

use App\Enums\WorkoutStatus;
use App\Models\PersonalRecord;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StatisticsService
{
    public function summary(User $user): array
    {
        $workouts = $this->completedWorkouts($user)->get();

        $totalVolume = $workouts->sum(fn (Workout $workout) => $this->workoutVolume($workout));
        $totalDuration = (int) $workouts->sum('duration_seconds');
        $totalSets = (int) $workouts->sum(fn (Workout $workout) => $workout->workoutExercises->sum(fn (WorkoutExercise $exercise) => $exercise->sets->count()));

        $topExercises = $workouts
            ->flatMap(fn (Workout $workout) => $workout->workoutExercises->pluck('exercise.name'))
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->all();

        $muscleGroups = $workouts
            ->flatMap(fn (Workout $workout) => $workout->workoutExercises->map(fn (WorkoutExercise $exercise) => data_get($exercise, 'exercise.muscleGroup.name')))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->all();

        $weekWorkouts = $this->completedWorkouts($user)->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $monthWorkouts = $this->completedWorkouts($user)->whereBetween('completed_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $recordsThisMonth = PersonalRecord::query()
            ->where('user_id', $user->id)
            ->whereBetween('achieved_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        return [
            'week_workouts' => $weekWorkouts,
            'month_workouts' => $monthWorkouts,
            'total_duration_seconds' => $totalDuration,
            'total_sets' => $totalSets,
            'total_volume' => $totalVolume,
            'top_exercises' => $topExercises,
            'muscle_groups' => $muscleGroups,
            'records_this_month' => $recordsThisMonth,
            'streak_weeks' => $this->currentStreakWeeks($user),
            'volume_change_percent' => $this->latestVolumeChangePercent($user),
        ];
    }

    public function history(User $user, int $limit = 10): Collection
    {
        return $this->completedWorkouts($user)
            ->latest('completed_at')
            ->limit($limit)
            ->get();
    }

    public function workoutDetails(Workout $workout): Workout
    {
        return $workout->loadMissing(['workoutExercises.exercise.muscleGroup', 'workoutExercises.sets']);
    }

    private function completedWorkouts(User $user)
    {
        return Workout::query()
            ->with(['workoutExercises.exercise.muscleGroup', 'workoutExercises.sets'])
            ->where('user_id', $user->id)
            ->where('status', WorkoutStatus::Completed);
    }

    private function workoutVolume(Workout $workout): float
    {
        return (float) $workout->workoutExercises
            ->flatMap(fn (WorkoutExercise $exercise) => $exercise->sets)
            ->sum(fn (WorkoutSet $set) => round(((float) $set->weight) * (int) $set->repetitions, 2));
    }

    private function currentStreakWeeks(User $user): int
    {
        $weeks = $this->completedWorkouts($user)
            ->get()
            ->map(fn (Workout $workout) => $workout->completed_at?->copy()->startOfWeek()->toDateString())
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        $streak = 0;
        $cursor = now()->startOfWeek()->toDateString();

        while ($weeks->contains($cursor)) {
            $streak++;
            $cursor = Carbon::parse($cursor)->subWeek()->startOfWeek()->toDateString();
        }

        return $streak;
    }

    private function latestVolumeChangePercent(User $user): ?float
    {
        $workouts = $this->completedWorkouts($user)->latest('completed_at')->take(2)->get();

        if ($workouts->count() < 2) {
            return null;
        }

        $latest = $this->workoutVolume($workouts->first());
        $previous = $this->workoutVolume($workouts->last());

        if ($previous <= 0) {
            return null;
        }

        return round((($latest - $previous) / $previous) * 100, 1);
    }
}
