<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class WorkoutExerciseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exercise_id' => ['required', 'integer', 'exists:exercises,id'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
