<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'muscle_group_id',
        'name',
        'slug',
        'description',
        'media_type',
        'media_value',
        'is_custom',
        'is_active',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'muscle_group_id' => 'integer',
        'is_custom' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(ExerciseTranslation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function muscleGroup(): BelongsTo
    {
        return $this->belongsTo(MuscleGroup::class);
    }

    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class);
    }

    public function templateExercises(): HasMany
    {
        return $this->hasMany(WorkoutTemplateExercise::class);
    }

    public function personalRecords(): HasMany
    {
        return $this->hasMany(PersonalRecord::class);
    }

    public function getNameAttribute(?string $value): string
    {
        return $this->translatedName() ?? (string) $value;
    }

    public function getDescriptionAttribute(?string $value): ?string
    {
        $translated = $this->translatedDescription();

        return $translated !== null ? $translated : $value;
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

    public function translatedDescription(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $translations = $this->relationLoaded('translations')
            ? $this->getRelation('translations')
            : $this->translations()->get();

        $translation = $translations->firstWhere('locale', $locale);

        if ($translation !== null && trim((string) $translation->description) !== '') {
            return (string) $translation->description;
        }

        $fallbackLocale = (string) config('app.fallback_locale', config('app.locale', 'en'));

        if ($fallbackLocale !== $locale) {
            $fallback = $translations->firstWhere('locale', $fallbackLocale);

            if ($fallback !== null && trim((string) $fallback->description) !== '') {
                return (string) $fallback->description;
            }
        }

        return null;
    }
}
