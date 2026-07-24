<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class WorkoutStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'workout_template_id' => ['sometimes', 'nullable', 'integer', 'exists:workout_templates,id'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'user_body_weight' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1000'],
        ];
    }
}
