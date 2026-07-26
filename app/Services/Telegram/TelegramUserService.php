<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TelegramUserService
{
    private const SUPPORTED_LOCALES = ['en', 'ru', 'hy'];

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
        $preferredLanguage = $this->resolvePreferredLanguage($telegramUser);
        $name = trim($firstName.' '.$lastName);

        if ($name === '') {
            $name = $username !== '' ? $username : 'Telegram User';
        }

        return DB::transaction(function () use ($telegramId, $name, $username, $preferredLanguage): User {
            $user = User::query()->firstOrNew(['telegram_id' => $telegramId]);

            $user->name = $name;
            $user->telegram_username = $username !== '' ? $username : null;
            $user->timezone = 'Asia/Yerevan';
            $user->weight_unit = 'kg';
            $user->email = null;
            $user->password = null;

            if (! $user->exists || ! $this->isSupportedLocale((string) $user->preferred_language)) {
                $user->preferred_language = $preferredLanguage;
            }

            $user->save();

            return $user->refresh();
        });
    }

    public function localeForUser(User $user): string
    {
        $locale = trim((string) $user->preferred_language);

        if ($this->isSupportedLocale($locale)) {
            return $locale;
        }

        $fallback = (string) config('app.locale', 'en');

        return $this->isSupportedLocale($fallback) ? $fallback : 'en';
    }

    public function updatePreferredLanguage(User $user, string $locale): User
    {
        $normalized = $this->normalizeLocale($locale);

        if ($normalized === null) {
            return $user;
        }

        if ($user->preferred_language === $normalized) {
            return $user;
        }

        $user->forceFill(['preferred_language' => $normalized])->save();

        return $user->refresh();
    }

    public function isSupportedLocale(string $locale): bool
    {
        return in_array(strtolower(trim($locale)), self::SUPPORTED_LOCALES, true);
    }

    private function resolvePreferredLanguage(array $telegramUser): string
    {
        $languageCode = (string) data_get($telegramUser, 'language_code', '');
        $normalized = $this->normalizeLocale($languageCode);

        if ($normalized !== null) {
            return $normalized;
        }

        $fallback = (string) config('app.locale', 'en');

        return $this->isSupportedLocale($fallback) ? $fallback : 'en';
    }

    private function normalizeLocale(string $locale): ?string
    {
        $locale = strtolower(trim($locale));

        if ($this->isSupportedLocale($locale)) {
            return $locale;
        }

        return null;
    }
}
