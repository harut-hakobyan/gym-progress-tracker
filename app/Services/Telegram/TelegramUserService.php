<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TelegramUserService
{
    public function resolveFromMessage(array $message): User
    {
        $telegramUser = (array) data_get($message, 'from', []);

        return $this->upsertTelegramUser($telegramUser);
    }

    public function resolveFromCallbackQuery(array $callbackQuery): ?User
    {
        $telegramUser = (array) data_get($callbackQuery, 'from', []);

        return $this->upsertTelegramUser($telegramUser);
    }

    public function upsertTelegramUser(array $telegramUser): User
    {
        $telegramId = (int) data_get($telegramUser, 'id', 0);
        $firstName = trim((string) data_get($telegramUser, 'first_name', ''));
        $lastName = trim((string) data_get($telegramUser, 'last_name', ''));
        $username = trim((string) data_get($telegramUser, 'username', ''));
        $name = trim($firstName.' '.$lastName);

        if ($name === '') {
            $name = $username !== '' ? $username : 'Telegram User';
        }

        return DB::transaction(function () use ($telegramId, $name, $username): User {
            return User::query()->updateOrCreate(
                ['telegram_id' => $telegramId],
                [
                    'name' => $name,
                    'telegram_username' => $username !== '' ? $username : null,
                    'preferred_language' => 'ru',
                    'timezone' => 'Asia/Yerevan',
                    'weight_unit' => 'kg',
                    'email' => null,
                    'password' => null,
                ]
            );
        });
    }
}
