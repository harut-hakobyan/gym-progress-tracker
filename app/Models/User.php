<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'telegram_id',
        'telegram_username',
        'email',
        'password',
        'preferred_language',
        'timezone',
        'weight_unit',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'telegram_id' => 'integer',
        'password' => 'hashed',
    ];

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }

    public function workoutTemplates(): HasMany
    {
        return $this->hasMany(WorkoutTemplate::class);
    }

    public function telegramState(): HasOne
    {
        return $this->hasOne(UserTelegramState::class);
    }

    public function personalRecords(): HasMany
    {
        return $this->hasMany(PersonalRecord::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(UserGoal::class);
    }
}
