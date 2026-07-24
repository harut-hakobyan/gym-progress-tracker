<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WorkoutExercise */
class WorkoutExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workout_id' => $this->workout_id,
            'exercise_id' => $this->exercise_id,
            'position' => $this->position,
            'notes' => $this->notes,
            'exercise' => $this->whenLoaded('exercise', fn () => [
                'id' => $this->exercise->id,
                'name' => $this->exercise->name,
                'slug' => $this->exercise->slug,
            ]),
            'sets' => WorkoutSetResource::collection($this->whenLoaded('sets')),
        ];
    }
}
