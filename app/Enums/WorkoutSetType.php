<?php

namespace App\Enums;

enum WorkoutSetType: string
{
    case Warmup = 'warmup';
    case Working = 'working';
    case Dropset = 'dropset';
    case Failure = 'failure';
}
