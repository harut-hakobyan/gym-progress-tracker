<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MuscleGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(MuscleGroupTranslation::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function getNameAttribute(?string $value): string
    {
        return $this->translatedName() ?? (string) $value;
    }

    public function translatedName(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $translations = $this->relationLoaded('translations')
            ? $this->getRelation('translations')
            : $this->translations()->get();

        $translation = $translations->firstWhere('locale', $locale);

        if ($translation !== null && trim((string) $translation->name) !== '') {
            return (string) $translation->name;
        }

        $fallbackLocale = (string) config('app.fallback_locale', config('app.locale', 'en'));

        if ($fallbackLocale !== $locale) {
            $fallback = $translations->firstWhere('locale', $fallbackLocale);

            if ($fallback !== null && trim((string) $fallback->name) !== '') {
                return (string) $fallback->name;
            }
        }

        return null;
    }
}
