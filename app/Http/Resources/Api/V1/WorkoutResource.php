<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Workout */
class WorkoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'workout_template_id' => $this->workout_template_id,
            'name' => $this->name,
            'status' => $this->status?->value ?? $this->status,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'duration_seconds' => $this->duration_seconds,
            'user_body_weight' => $this->user_body_weight,
            'notes' => $this->notes,
            'exercises' => WorkoutExerciseResource::collection($this->whenLoaded('workoutExercises')),
        ];
    }
}
