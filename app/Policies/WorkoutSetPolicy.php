<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutSet;

class WorkoutSetPolicy
{
    public function view(User $user, WorkoutSet $workoutSet): bool
    {
        $workoutSet->loadMissing('workoutExercise.workout');

        return (int) $workoutSet->workoutExercise?->workout?->user_id === $user->id;
    }

    public function update(User $user, WorkoutSet $workoutSet): bool
    {
        $workoutSet->loadMissing('workoutExercise.workout');

        return (int) $workoutSet->workoutExercise?->workout?->user_id === $user->id;
    }

    public function delete(User $user, WorkoutSet $workoutSet): bool
    {
        $workoutSet->loadMissing('workoutExercise.workout');

        return (int) $workoutSet->workoutExercise?->workout?->user_id === $user->id;
    }
}
