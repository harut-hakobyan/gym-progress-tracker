<?php

namespace App\Services\Telegram;

use App\Enums\TelegramState;
use App\Models\User;
use App\Models\UserTelegramState;
use Carbon\CarbonInterface;

class TelegramStateService
{
    public function get(User $user): ?UserTelegramState
    {
        $state = UserTelegramState::query()
            ->where('user_id', $user->id)
            ->first();

        if ($state === null) {
            return null;
        }

        if ($state->expires_at !== null && $state->expires_at->isPast()) {
            $state->delete();

            return null;
        }

        return $state;
    }

    public function put(User $user, TelegramState|string $state, array $payload = [], ?CarbonInterface $expiresAt = null): UserTelegramState
    {
        $expiresAt ??= now()->addMinutes((int) config('telegram.state_ttl_minutes', 120));

        return UserTelegramState::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'state' => $state instanceof TelegramState ? $state->value : $state,
                'payload' => $payload,
                'expires_at' => $expiresAt,
            ]
        );
    }

    public function forget(User $user): void
    {
        UserTelegramState::query()->where('user_id', $user->id)->delete();
    }
}
