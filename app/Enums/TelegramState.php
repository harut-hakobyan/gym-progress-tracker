<?php

namespace App\Enums;

enum TelegramState: string
{
    case AwaitingWorkoutName = 'awaiting_workout_name';
    case AwaitingExerciseSelection = 'awaiting_exercise_selection';
    case AwaitingSetWeight = 'awaiting_set_weight';
    case AwaitingSetRepetitions = 'awaiting_set_repetitions';
    case AwaitingTemplateName = 'awaiting_template_name';
    case AwaitingTemplateDayOfWeek = 'awaiting_template_day_of_week';
    case AwaitingTemplateRename = 'awaiting_template_rename';
    case AwaitingTemplateDescription = 'awaiting_template_description';
    case AwaitingTemplateMuscleGroups = 'awaiting_template_muscle_groups';
    case AwaitingTemplateExercises = 'awaiting_template_exercises';
    case AwaitingGoalType = 'awaiting_goal_type';
    case AwaitingGoalValue = 'awaiting_goal_value';
    case AwaitingGoalDate = 'awaiting_goal_date';
}
