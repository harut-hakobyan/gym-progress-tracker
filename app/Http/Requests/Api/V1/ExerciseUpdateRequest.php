<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ExerciseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'muscle_group_id' => ['sometimes', 'integer', 'exists:muscle_groups,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'media_type' => ['sometimes', 'nullable', 'string', 'in:photo,animation'],
            'media_value' => ['sometimes', 'nullable', 'string'],
            'is_custom' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
