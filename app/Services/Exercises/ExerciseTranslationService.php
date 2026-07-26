<?php

namespace App\Services\Exercises;

use App\Models\Exercise;
use App\Models\ExerciseTranslation;
use Illuminate\Support\Facades\DB;

class ExerciseTranslationService
{
    private const SUPPORTED_LOCALES = ['ru', 'en', 'hy'];

    public function supportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    public function orderedLocales(?string $primaryLocale = null): array
    {
        $primaryLocale = $this->normalizeLocale($primaryLocale);
        $locales = self::SUPPORTED_LOCALES;

        if ($primaryLocale !== null && in_array($primaryLocale, $locales, true)) {
            $locales = array_values(array_unique(array_merge([$primaryLocale], $locales)));
        }

        return $locales;
    }

    public function upsertTranslation(Exercise $exercise, string $locale, ?string $name, ?string $description = null): void
    {
        $locale = $this->normalizeLocale($locale);

        if ($locale === null) {
            return;
        }

        $name = $this->normalizeText($name);
        $description = $this->normalizeText($description);

        if ($name === null) {
            return;
        }

        ExerciseTranslation::query()->updateOrCreate(
            [
                'exercise_id' => $exercise->id,
                'locale' => $locale,
            ],
            [
                'name' => $name ?? '',
                'description' => $description,
            ]
        );
    }

    /**
     * @param array<string, array{name?: ?string, description?: ?string}> $translations
     */
    public function syncTranslations(Exercise $exercise, array $translations): void
    {
        DB::transaction(function () use ($exercise, $translations): void {
            foreach ($translations as $locale => $data) {
                if (! is_array($data)) {
                    continue;
                }

                $this->upsertTranslation(
                    $exercise,
                    (string) $locale,
                    $data['name'] ?? null,
                    $data['description'] ?? null,
                );
            }
        });
    }

    private function normalizeLocale(?string $locale): ?string
    {
        $locale = strtolower(trim((string) $locale));

        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : null;
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || $value === '-') {
            return null;
        }

        return $value;
    }
}
