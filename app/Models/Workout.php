<?php

namespace App\Models;

use App\Enums\WorkoutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workout_template_id',
        'name',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'user_body_weight',
        'notes',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'workout_template_id' => 'integer',
        'status' => WorkoutStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'user_body_weight' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkoutTemplate::class, 'workout_template_id');
    }

    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class);
    }

    public function sets(): HasMany
    {
        return $this->hasManyThrough(WorkoutSet::class, WorkoutExercise::class);
    }
}
