<?php

namespace App\Enums;

enum UserGoalStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
