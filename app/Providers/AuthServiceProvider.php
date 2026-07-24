<?php

namespace App\Providers;

use App\Models\Exercise;
use App\Models\Workout;
use App\Models\WorkoutSet;
use App\Models\WorkoutTemplate;
use App\Models\UserGoal;
use App\Policies\ExercisePolicy;
use App\Policies\WorkoutPolicy;
use App\Policies\WorkoutSetPolicy;
use App\Policies\WorkoutTemplatePolicy;
use App\Policies\UserGoalPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Exercise::class => ExercisePolicy::class,
        Workout::class => WorkoutPolicy::class,
        WorkoutTemplate::class => WorkoutTemplatePolicy::class,
        WorkoutSet::class => WorkoutSetPolicy::class,
        UserGoal::class => UserGoalPolicy::class,
    ];
}
