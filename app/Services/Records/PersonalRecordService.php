<?php

namespace App\Services\Records;

use App\Models\Exercise;
use App\Models\PersonalRecord;
use App\Models\User;
use App\Models\WorkoutSet;
use App\Services\Workouts\WorkoutMetricsService;
use Illuminate\Database\Eloquent\Collection;

class PersonalRecordService
{
    public function __construct(
        private readonly WorkoutMetricsService $metrics,
    ) {
    }

    public function syncFromWorkoutSet(WorkoutSet $workoutSet): array
    {
        $workoutSet->loadMissing(['workoutExercise.workout', 'workoutExercise.exercise']);

        $userId = (int) $workoutSet->workoutExercise->workout->user_id;
        $exerciseId = (int) $workoutSet->workoutExercise->exercise_id;

        $currentValues = [
            'max_weight' => (float) $workoutSet->weight,
            'max_repetitions' => (float) $workoutSet->repetitions,
            'max_volume' => $this->metrics->setVolume((float) $workoutSet->weight, (int) $workoutSet->repetitions),
            'estimated_one_rep_max' => $this->metrics->estimatedOneRepMax((float) $workoutSet->weight, (int) $workoutSet->repetitions),
        ];

        $updatedTypes = [];

        foreach ($currentValues as $type => $value) {
            if ($this->syncRecord($userId, $exerciseId, $type, $value, $workoutSet->id, $workoutSet->completed_at)) {
                $updatedTypes[] = $type;
            }
        }

        return $updatedTypes;
    }

    public function rebuildForExercise(User $user, Exercise $exercise): void
    {
        PersonalRecord::query()
            ->where('user_id', $user->id)
            ->where('exercise_id', $exercise->id)
            ->delete();

        $sets = WorkoutSet::query()
            ->whereHas('workoutExercise.workout', fn ($query) => $query->where('user_id', $user->id))
            ->whereHas('workoutExercise.exercise', fn ($query) => $query->whereKey($exercise->id))
            ->with(['workoutExercise.workout', 'workoutExercise.exercise'])
            ->get();

        if ($sets->isEmpty()) {
            return;
        }

        $bestSets = [
            'max_weight' => $sets->sortByDesc(fn (WorkoutSet $set) => (float) $set->weight)->first(),
            'max_repetitions' => $sets->sortByDesc(fn (WorkoutSet $set) => (int) $set->repetitions)->first(),
            'max_volume' => $sets->sortByDesc(fn (WorkoutSet $set) => $this->metrics->setVolume((float) $set->weight, (int) $set->repetitions))->first(),
            'estimated_one_rep_max' => $sets->sortByDesc(fn (WorkoutSet $set) => $this->metrics->estimatedOneRepMax((float) $set->weight, (int) $set->repetitions))->first(),
        ];

        foreach ($bestSets as $type => $set) {
            if ($set === null) {
                continue;
            }

            $this->syncRecord(
                $user->id,
                $exercise->id,
                $type,
                $this->recordValueForType($type, $set),
                $set->id,
                $set->completed_at
            );
        }
    }

    public function latestForUser(User $user, int $limit = 10): Collection
    {
        return PersonalRecord::query()
            ->with('exercise')
            ->where('user_id', $user->id)
            ->latest('achieved_at')
            ->limit($limit)
            ->get();
    }

    private function syncRecord(int $userId, int $exerciseId, string $type, float $value, int $workoutSetId, ?\DateTimeInterface $achievedAt): bool
    {
        $current = PersonalRecord::query()
            ->where('user_id', $userId)
            ->where('exercise_id', $exerciseId)
            ->where('type', $type)
            ->first();

        if ($current !== null && ! $this->isImprovement($type, (float) $current->value, $value)) {
            return false;
        }

        PersonalRecord::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'exercise_id' => $exerciseId,
                'type' => $type,
            ],
            [
                'workout_set_id' => $workoutSetId,
                'value' => $value,
                'achieved_at' => $achievedAt ?? now(),
            ]
        );

        return true;
    }

    private function recordValueForType(string $type, WorkoutSet $set): float
    {
        return match ($type) {
            'max_weight' => (float) $set->weight,
            'max_repetitions' => (float) $set->repetitions,
            'max_volume' => $this->metrics->setVolume((float) $set->weight, (int) $set->repetitions),
            'estimated_one_rep_max' => $this->metrics->estimatedOneRepMax((float) $set->weight, (int) $set->repetitions),
            default => 0.0,
        };
    }

    private function isImprovement(string $type, float $current, float $incoming): bool
    {
        return $incoming > $current;
    }
}
