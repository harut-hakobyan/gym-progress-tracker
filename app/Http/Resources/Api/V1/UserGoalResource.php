<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\UserGoal */
class UserGoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'exercise_id' => $this->exercise_id,
            'type' => $this->type?->value ?? $this->type,
            'target_value' => $this->target_value,
            'target_date' => $this->target_date?->toDateString(),
            'status' => $this->status?->value ?? $this->status,
            'exercise' => $this->whenLoaded('exercise', fn () => [
                'id' => $this->exercise->id,
                'name' => $this->exercise->name,
            ]),
        ];
    }
}
