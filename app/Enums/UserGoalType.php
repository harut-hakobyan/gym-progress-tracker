<?php

namespace App\Enums;

enum UserGoalType: string
{
    case TargetWeight = 'target_weight';
    case TargetOneRepMax = 'target_one_rep_max';
    case TargetBodyWeight = 'target_body_weight';
    case WeeklyWorkouts = 'weekly_workouts';
}
