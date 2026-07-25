<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Exercise */
class ExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'muscle_group_id' => $this->muscle_group_id,
            'muscle_group' => $this->whenLoaded('muscleGroup', fn () => [
                'id' => $this->muscleGroup->id,
                'name' => $this->muscleGroup->name,
                'slug' => $this->muscleGroup->slug,
            ]),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'media_type' => $this->media_type,
            'media_value' => $this->media_value,
            'is_custom' => $this->is_custom,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
