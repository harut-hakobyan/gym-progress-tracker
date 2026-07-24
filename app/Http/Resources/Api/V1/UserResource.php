<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'telegram_id' => $this->telegram_id,
            'telegram_username' => $this->telegram_username,
            'email' => $this->email,
            'preferred_language' => $this->preferred_language,
            'timezone' => $this->timezone,
            'weight_unit' => $this->weight_unit,
        ];
    }
}
