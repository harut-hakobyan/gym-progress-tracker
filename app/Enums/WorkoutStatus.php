<?php

namespace App\Enums;

enum WorkoutStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
