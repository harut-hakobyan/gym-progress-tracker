<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class WorkoutTemplateExerciseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'position' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'target_sets' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
            'target_repetitions_min' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
            'target_repetitions_max' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
            'target_weight' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1000'],
            'rest_seconds' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:3600'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
