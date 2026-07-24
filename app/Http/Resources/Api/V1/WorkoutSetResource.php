<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WorkoutSet */
class WorkoutSetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workout_exercise_id' => $this->workout_exercise_id,
            'set_number' => $this->set_number,
            'type' => $this->type?->value ?? $this->type,
            'weight' => $this->weight,
            'repetitions' => $this->repetitions,
            'duration_seconds' => $this->duration_seconds,
            'distance_meters' => $this->distance_meters,
            'rpe' => $this->rpe,
            'rir' => $this->rir,
            'rest_seconds' => $this->rest_seconds,
            'is_completed' => $this->is_completed,
            'completed_at' => $this->completed_at?->toISOString(),
            'notes' => $this->notes,
        ];
    }
}
