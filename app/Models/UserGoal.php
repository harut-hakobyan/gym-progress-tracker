<?php

namespace App\Models;

use App\Enums\UserGoalStatus;
use App\Enums\UserGoalType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exercise_id',
        'type',
        'target_value',
        'target_date',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'exercise_id' => 'integer',
        'type' => UserGoalType::class,
        'target_value' => 'decimal:2',
        'target_date' => 'date',
        'status' => UserGoalStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
