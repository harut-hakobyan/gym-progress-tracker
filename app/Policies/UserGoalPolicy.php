<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserGoal;

class UserGoalPolicy
{
    public function view(User $user, UserGoal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, UserGoal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function delete(User $user, UserGoal $goal): bool
    {
        return $goal->user_id === $user->id;
    }
}
