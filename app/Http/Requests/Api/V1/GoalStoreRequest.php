<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\UserGoalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoalStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exercise_id' => ['sometimes', 'nullable', 'integer', 'exists:exercises,id'],
            'type' => ['required', Rule::enum(UserGoalType::class)],
            'target_value' => ['required', 'numeric', 'min:0', 'max:10000'],
            'target_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'nullable', 'in:active,completed,cancelled'],
        ];
    }
}
