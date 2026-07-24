<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WorkoutTemplateExercise */
class WorkoutTemplateExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workout_template_id' => $this->workout_template_id,
            'exercise_id' => $this->exercise_id,
            'position' => $this->position,
            'target_sets' => $this->target_sets,
            'target_repetitions_min' => $this->target_repetitions_min,
            'target_repetitions_max' => $this->target_repetitions_max,
            'target_weight' => $this->target_weight,
            'rest_seconds' => $this->rest_seconds,
            'notes' => $this->notes,
            'exercise' => $this->whenLoaded('exercise', fn () => [
                'id' => $this->exercise->id,
                'name' => $this->exercise->name,
                'slug' => $this->exercise->slug,
            ]),
        ];
    }
}
