<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PersonalRecord */
class PersonalRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'exercise_id' => $this->exercise_id,
            'workout_set_id' => $this->workout_set_id,
            'type' => $this->type,
            'value' => $this->value,
            'achieved_at' => $this->achieved_at?->toISOString(),
            'exercise' => $this->whenLoaded('exercise', fn () => [
                'id' => $this->exercise->id,
                'name' => $this->exercise->name,
            ]),
        ];
    }
}
