<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ExerciseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'muscle_group_id' => ['required', 'integer', 'exists:muscle_groups,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'translations' => ['sometimes', 'array'],
            'translations.*.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'translations.*.description' => ['sometimes', 'nullable', 'string'],
            'media_type' => ['sometimes', 'nullable', 'string', 'in:photo,animation'],
            'media_value' => ['sometimes', 'nullable', 'string'],
            'is_custom' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
