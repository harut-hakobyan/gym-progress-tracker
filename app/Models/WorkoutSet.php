<?php

namespace App\Models;

use App\Enums\WorkoutSetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_exercise_id',
        'set_number',
        'type',
        'weight',
        'repetitions',
        'duration_seconds',
        'distance_meters',
        'rpe',
        'rir',
        'rest_seconds',
        'is_completed',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'workout_exercise_id' => 'integer',
        'set_number' => 'integer',
        'type' => WorkoutSetType::class,
        'weight' => 'decimal:2',
        'repetitions' => 'integer',
        'duration_seconds' => 'integer',
        'distance_meters' => 'decimal:2',
        'rpe' => 'integer',
        'rir' => 'integer',
        'rest_seconds' => 'integer',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function workoutExercise(): BelongsTo
    {
        return $this->belongsTo(WorkoutExercise::class);
    }

    public function personalRecords(): HasMany
    {
        return $this->hasMany(PersonalRecord::class);
    }
}
