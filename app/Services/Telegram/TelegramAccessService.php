<?php

namespace App\Services\Telegram;

use App\Models\User;

class TelegramAccessService
{
    public function isAdmin(User $user): bool
    {
        $adminIds = array_map('intval', (array) config('telegram.admin_ids', []));

        return in_array((int) $user->telegram_id, $adminIds, true);
    }
}
