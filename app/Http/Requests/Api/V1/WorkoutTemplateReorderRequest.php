<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class WorkoutTemplateReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exercise_ids' => ['required', 'array', 'min:1'],
            'exercise_ids.*' => ['integer', 'distinct', 'exists:workout_template_exercises,id'],
        ];
    }
}
