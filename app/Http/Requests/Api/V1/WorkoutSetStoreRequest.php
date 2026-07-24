<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\WorkoutSetType;

class WorkoutSetStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'set_number' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'type' => ['sometimes', Rule::enum(WorkoutSetType::class)],
            'weight' => ['required', 'numeric', 'min:0', 'max:1000'],
            'repetitions' => ['required', 'integer', 'min:1', 'max:1000'],
            'duration_seconds' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:86400'],
            'distance_meters' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1000000'],
            'rpe' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10'],
            'rir' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:20'],
            'rest_seconds' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:3600'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
