<?php

namespace App\Enums;

enum TelegramState: string
{
    case AwaitingWorkoutName = 'awaiting_workout_name';
    case AwaitingExerciseSelection = 'awaiting_exercise_selection';
    case AwaitingSetWeight = 'awaiting_set_weight';
    case AwaitingSetRepetitions = 'awaiting_set_repetitions';
    case AwaitingSetRpe = 'awaiting_set_rpe';
    case AwaitingTemplateName = 'awaiting_template_name';
    case AwaitingGoalType = 'awaiting_goal_type';
    case AwaitingGoalValue = 'awaiting_goal_value';
    case AwaitingGoalDate = 'awaiting_goal_date';
}
