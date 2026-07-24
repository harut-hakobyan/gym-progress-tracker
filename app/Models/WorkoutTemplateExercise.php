<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutTemplateExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_template_id',
        'exercise_id',
        'position',
        'target_sets',
        'target_repetitions_min',
        'target_repetitions_max',
        'target_weight',
        'rest_seconds',
        'notes',
    ];

    protected $casts = [
        'workout_template_id' => 'integer',
        'exercise_id' => 'integer',
        'position' => 'integer',
        'target_sets' => 'integer',
        'target_repetitions_min' => 'integer',
        'target_repetitions_max' => 'integer',
        'target_weight' => 'decimal:2',
        'rest_seconds' => 'integer',
    ];

    public function workoutTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkoutTemplate::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
