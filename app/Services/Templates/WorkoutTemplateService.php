<?php

namespace App\Services\Templates;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Models\WorkoutTemplate;
use App\Models\WorkoutTemplateExercise;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkoutTemplateService
{
    public function addExercise(WorkoutTemplate $template, Exercise $exercise, array $data = []): WorkoutTemplateExercise
    {
        return DB::transaction(function () use ($template, $exercise, $data): WorkoutTemplateExercise {
            return WorkoutTemplateExercise::query()->create([
                'workout_template_id' => $template->id,
                'exercise_id' => $exercise->id,
                'position' => (int) (WorkoutTemplateExercise::query()
                    ->where('workout_template_id', $template->id)
                    ->max('position') ?? 0) + 1,
                'target_sets' => $data['target_sets'] ?? null,
                'target_repetitions_min' => $data['target_repetitions_min'] ?? null,
                'target_repetitions_max' => $data['target_repetitions_max'] ?? null,
                'target_weight' => $data['target_weight'] ?? null,
                'rest_seconds' => $data['rest_seconds'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function updateExercise(WorkoutTemplateExercise $templateExercise, array $data = []): WorkoutTemplateExercise
    {
        $templateExercise->update([
            'position' => $data['position'] ?? $templateExercise->position,
            'target_sets' => $data['target_sets'] ?? $templateExercise->target_sets,
            'target_repetitions_min' => $data['target_repetitions_min'] ?? $templateExercise->target_repetitions_min,
            'target_repetitions_max' => $data['target_repetitions_max'] ?? $templateExercise->target_repetitions_max,
            'target_weight' => $data['target_weight'] ?? $templateExercise->target_weight,
            'rest_seconds' => $data['rest_seconds'] ?? $templateExercise->rest_seconds,
            'notes' => $data['notes'] ?? $templateExercise->notes,
        ]);

        return $templateExercise->refresh();
    }

    public function removeExercise(WorkoutTemplateExercise $templateExercise): void
    {
        $templateExercise->delete();
    }

    public function reorder(WorkoutTemplate $template, array $orderedIds): void
    {
        DB::transaction(function () use ($template, $orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                WorkoutTemplateExercise::query()
                    ->where('workout_template_id', $template->id)
                    ->whereKey($id)
                    ->update(['position' => $index + 1]);
            }
        });
    }

    public function copy(User $user, WorkoutTemplate $template, ?string $name = null): WorkoutTemplate
    {
        return DB::transaction(function () use ($user, $template, $name): WorkoutTemplate {
            $template->loadMissing('templateExercises');

            $copy = WorkoutTemplate::query()->create([
                'user_id' => $user->id,
                'name' => $name ?: $template->name.' copy',
                'description' => $template->description,
                'is_active' => $template->is_active,
            ]);

            foreach ($template->templateExercises as $exercise) {
                WorkoutTemplateExercise::query()->create([
                    'workout_template_id' => $copy->id,
                    'exercise_id' => $exercise->exercise_id,
                    'position' => $exercise->position,
                    'target_sets' => $exercise->target_sets,
                    'target_repetitions_min' => $exercise->target_repetitions_min,
                    'target_repetitions_max' => $exercise->target_repetitions_max,
                    'target_weight' => $exercise->target_weight,
                    'rest_seconds' => $exercise->rest_seconds,
                    'notes' => $exercise->notes,
                ]);
            }

            return $copy;
        });
    }

    public function createFromMuscleGroups(User $user, string $name, array $muscleGroupIds, ?string $description = null): WorkoutTemplate
    {
        $groupIds = collect($muscleGroupIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return DB::transaction(function () use ($user, $name, $groupIds, $description): WorkoutTemplate {
            $groups = MuscleGroup::query()
                ->whereIn('id', $groupIds)
                ->get()
                ->keyBy('id');

            $template = WorkoutTemplate::query()->create([
                'user_id' => $user->id,
                'name' => $this->normalizeName($name),
                'description' => $description ?? $groups->pluck('name')->implode(' + '),
                'is_active' => true,
            ]);

            $position = 1;

            foreach ($groupIds as $groupId) {
                Exercise::query()
                    ->with('muscleGroup')
                    ->where('muscle_group_id', $groupId)
                    ->where('is_active', true)
                    ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
                    ->orderBy('name')
                    ->get()
                    ->each(function (Exercise $exercise) use ($template, &$position): void {
                        WorkoutTemplateExercise::query()->create([
                            'workout_template_id' => $template->id,
                            'exercise_id' => $exercise->id,
                            'position' => $position++,
                            'target_sets' => 3,
                            'target_repetitions_min' => 6,
                            'target_repetitions_max' => 10,
                            'target_weight' => null,
                            'rest_seconds' => 90,
                            'notes' => null,
                        ]);
                    });
            }

            return $template;
        });
    }

    public function normalizeName(string $name): string
    {
        return Str::of($name)->trim()->squish()->toString();
    }
}
