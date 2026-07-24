<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutTemplate;

class WorkoutTemplatePolicy
{
    public function view(User $user, WorkoutTemplate $template): bool
    {
        return $template->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WorkoutTemplate $template): bool
    {
        return $template->user_id === $user->id;
    }

    public function delete(User $user, WorkoutTemplate $template): bool
    {
        return $template->user_id === $user->id;
    }
}
