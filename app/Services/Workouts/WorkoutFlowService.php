<?php

namespace App\Services\Workouts;

use App\Enums\WorkoutSetType;
use App\Enums\WorkoutStatus;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use App\Models\WorkoutTemplate;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WorkoutFlowService
{
    public function __construct(
        private readonly WorkoutMetricsService $metrics,
    ) {
    }

    public function activeWorkout(User $user): ?Workout
    {
        return Workout::query()
            ->with(['workoutExercises.exercise', 'workoutExercises.sets'])
            ->where('user_id', $user->id)
            ->where('status', WorkoutStatus::Active)
            ->latest('started_at')
            ->first();
    }

    public function startWorkout(User $user, ?WorkoutTemplate $template = null): Workout
    {
        return DB::transaction(function () use ($user, $template): Workout {
            $active = $this->activeWorkout($user);

            if ($active !== null) {
                return $active;
            }

            $name = $template?->name ?? __('telegram.workout.default_name');

            return Workout::query()->create([
                'user_id' => $user->id,
                'workout_template_id' => $template?->id,
                'name' => $name,
                'status' => WorkoutStatus::Active,
                'started_at' => now(),
                'completed_at' => null,
                'duration_seconds' => null,
                'user_body_weight' => null,
                'notes' => null,
            ]);
        });
    }

    public function attachExercise(Workout $workout, Exercise $exercise): WorkoutExercise
    {
        return DB::transaction(function () use ($workout, $exercise): WorkoutExercise {
            return WorkoutExercise::query()->firstOrCreate(
                [
                    'workout_id' => $workout->id,
                    'exercise_id' => $exercise->id,
                ],
                [
                    'position' => (int) (WorkoutExercise::query()
                        ->where('workout_id', $workout->id)
                        ->max('position') ?? 0) + 1,
                    'notes' => null,
                ]
            );
        });
    }

    public function addSet(
        WorkoutExercise $workoutExercise,
        float $weight,
        int $repetitions,
        ?int $rpe = null,
        ?int $rir = null,
        WorkoutSetType $type = WorkoutSetType::Working,
        ?int $restSeconds = null,
        ?string $notes = null,
    ): WorkoutSet {
        return DB::transaction(function () use ($workoutExercise, $weight, $repetitions, $rpe, $rir, $type, $restSeconds, $notes): WorkoutSet {
            $setNumber = (int) (WorkoutSet::query()
                ->where('workout_exercise_id', $workoutExercise->id)
                ->max('set_number') ?? 0) + 1;

            return WorkoutSet::query()->create([
                'workout_exercise_id' => $workoutExercise->id,
                'set_number' => $setNumber,
                'type' => $type,
                'weight' => $this->metrics->roundWeight($weight),
                'repetitions' => $repetitions,
                'duration_seconds' => null,
                'distance_meters' => null,
                'rpe' => $rpe,
                'rir' => $rir,
                'rest_seconds' => $restSeconds,
                'is_completed' => true,
                'completed_at' => now(),
                'notes' => $notes,
            ]);
            });
    }

    public function updateSetRpe(WorkoutSet $set, ?int $rpe): WorkoutSet
    {
        $set->update([
            'rpe' => $rpe,
        ]);

        return $set->refresh();
    }

    public function workoutExercises(Workout $workout): Collection
    {
        return $workout->workoutExercises()->with('exercise', 'sets')->orderBy('position')->get();
    }

    public function availableTemplates(User $user): Collection
    {
        return WorkoutTemplate::query()
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
            ->where('is_active', true)
            ->orderByRaw('user_id is not null desc')
            ->orderBy('name')
            ->get();
    }

    public function templateForUser(User $user, int $templateId): ?WorkoutTemplate
    {
        return WorkoutTemplate::query()
            ->whereKey($templateId)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
            ->first();
    }

    public function availableExercises(User $user): Collection
    {
        return Exercise::query()
            ->with('muscleGroup')
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
            ->orderBy('name')
            ->get();
    }

    public function exerciseForUser(User $user, int $exerciseId): ?Exercise
    {
        return Exercise::query()
            ->whereKey($exerciseId)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
            ->first();
    }

    public function exerciseOverview(User $user, Exercise $exercise): array
    {
        $lastSet = WorkoutSet::query()
            ->whereHas('workoutExercise', fn ($query) => $query->whereHas('workout', fn ($workoutQuery) => $workoutQuery->where('user_id', $user->id)->where('status', WorkoutStatus::Completed)))
            ->whereHas('workoutExercise.exercise', fn ($query) => $query->whereKey($exercise->id))
            ->latest('completed_at')
            ->first();

        $bestWeight = WorkoutSet::query()
            ->whereHas('workoutExercise', fn ($query) => $query->whereHas('workout', fn ($workoutQuery) => $workoutQuery->where('user_id', $user->id)->where('status', WorkoutStatus::Completed)))
            ->whereHas('workoutExercise.exercise', fn ($query) => $query->whereKey($exercise->id))
            ->max('weight');

        $bestOneRepMax = WorkoutSet::query()
            ->whereHas('workoutExercise', fn ($query) => $query->whereHas('workout', fn ($workoutQuery) => $workoutQuery->where('user_id', $user->id)->where('status', WorkoutStatus::Completed)))
            ->whereHas('workoutExercise.exercise', fn ($query) => $query->whereKey($exercise->id))
            ->get()
            ->map(fn (WorkoutSet $set) => $this->metrics->estimatedOneRepMax((float) $set->weight, $set->repetitions))
            ->max();

        $recommendationWeight = $lastSet !== null
            ? $this->metrics->roundWeight(((float) $lastSet->weight) + 2.5)
            : 0.0;

        return [
            'last_set' => $lastSet,
            'best_weight' => $bestWeight !== null ? (float) $bestWeight : null,
            'best_one_rep_max' => $bestOneRepMax,
            'recommended_weight' => $recommendationWeight,
        ];
    }

    public function completeWorkout(Workout $workout): array
    {
        return DB::transaction(function () use ($workout): array {
            $workout->loadMissing(['workoutExercises.sets']);

            $completedAt = now();
            $duration = $workout->started_at instanceof CarbonInterface
                ? $workout->started_at->diffInSeconds($completedAt)
                : null;

            $volume = $this->workoutVolume($workout);
            $exerciseCount = $workout->workoutExercises->count();
            $setCount = $workout->workoutExercises->sum(fn (WorkoutExercise $exercise) => $exercise->sets->count());

            $workout->update([
                'status' => WorkoutStatus::Completed,
                'completed_at' => $completedAt,
                'duration_seconds' => $duration,
            ]);

            return [
                'duration_seconds' => $duration,
                'exercise_count' => $exerciseCount,
                'set_count' => $setCount,
                'volume' => $volume,
            ];
        });
    }

    public function workoutVolume(Workout $workout): float
    {
        $workout->loadMissing(['workoutExercises.sets']);

        return (float) $workout->workoutExercises
            ->flatMap(fn (WorkoutExercise $exercise) => $exercise->sets)
            ->sum(fn (WorkoutSet $set) => $this->metrics->setVolume((float) $set->weight, (int) $set->repetitions));
    }

    public function workoutExerciseById(User $user, int $workoutExerciseId): ?WorkoutExercise
    {
        return WorkoutExercise::query()
            ->whereKey($workoutExerciseId)
            ->whereHas('workout', fn ($query) => $query->where('user_id', $user->id))
            ->with(['exercise', 'sets'])
            ->first();
    }

    public function workoutById(User $user, int $workoutId): ?Workout
    {
        return Workout::query()
            ->whereKey($workoutId)
            ->where('user_id', $user->id)
            ->with(['workoutExercises.exercise', 'workoutExercises.sets'])
            ->first();
    }

    public function lastSet(WorkoutExercise $workoutExercise): ?WorkoutSet
    {
        return $workoutExercise->sets()->latest('set_number')->first();
    }
}
