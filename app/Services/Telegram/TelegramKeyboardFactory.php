<?php

namespace App\Services\Telegram;

class TelegramKeyboardFactory
{
    public function mainMenu(bool $isAdmin = false): array
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => __('telegram.buttons.start_workout'), 'callback_data' => 'workout:start'],
                ],
                [
                    ['text' => __('telegram.buttons.templates'), 'callback_data' => 'templates:list'],
                    ['text' => __('telegram.buttons.stats'), 'callback_data' => 'stats:summary'],
                ],
                [
                    ['text' => __('telegram.buttons.records'), 'callback_data' => 'records:list'],
                    ['text' => __('telegram.buttons.goals'), 'callback_data' => 'goals:list'],
                ],
                [
                    ['text' => __('telegram.buttons.history'), 'callback_data' => 'history:list'],
                    ['text' => __('telegram.buttons.settings'), 'callback_data' => 'settings:main'],
                ],
            ],
        ];

        if ($isAdmin) {
            $keyboard['inline_keyboard'][] = [
                ['text' => __('telegram.buttons.admin_menu'), 'callback_data' => 'admin:menu'],
            ];
        }

        return $keyboard;
    }

    public function cancelOnly(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => __('telegram.buttons.cancel'), 'callback_data' => 'common:cancel'],
                ],
            ],
        ];
    }

    public function workoutTemplates(array $templates): array
    {
        $keyboard = [];

        foreach ($templates as $template) {
            $keyboard[] = [
                [
                    'text' => $template['name'],
                    'callback_data' => 'workout:template:'.$template['id'],
                ],
            ];
        }

        $keyboard[] = [
            [
                'text' => __('telegram.templates.create'),
                'callback_data' => 'templates:create',
            ],
            [
                'text' => __('telegram.buttons.standard_templates'),
                'callback_data' => 'workout:templates:standard',
            ],
        ];

        $keyboard[] = [
            [
                'text' => __('telegram.buttons.back'),
                'callback_data' => 'common:menu',
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function workoutStandardTemplates(array $templates): array
    {
        $keyboard = [];

        foreach ($templates as $template) {
            $keyboard[] = [
                [
                    'text' => $template['name'],
                    'callback_data' => 'workout:template:'.$template['id'],
                ],
            ];
        }

        $keyboard[] = [
            [
                'text' => __('telegram.workout.empty_workout'),
                'callback_data' => 'workout:template:empty',
            ],
            [
                'text' => __('telegram.workout.back_to_custom_templates'),
                'callback_data' => 'workout:templates:custom',
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }


    public function exerciseSelection(array $exercises): array
    {
        $keyboard = [];
        $row = [];

        foreach ($exercises as $exercise) {
            $row[] = [
                'text' => $exercise['name'],
                'callback_data' => 'workout:exercise:'.$exercise['id'],
            ];

            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $keyboard[] = $row;
        }

        $keyboard[] = [
            [
                'text' => __('telegram.buttons.complete_workout'),
                'callback_data' => 'workout:complete:current',
            ],
            [
                'text' => __('telegram.buttons.back'),
                'callback_data' => 'workout:back:current',
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function workoutExerciseActions(int $workoutExerciseId, int $exerciseId, bool $canRepeat = false, array $recentSets = []): array
    {
        $keyboard = [
            [
                [
                    'text' => __('telegram.buttons.add_set'),
                    'callback_data' => 'set:add:'.$workoutExerciseId,
                ],
            ],
            [
                [
                    'text' => __('telegram.buttons.progress'),
                    'callback_data' => 'exercise:progress:'.$exerciseId,
                ],
            ],
        ];

        if ($recentSets !== []) {
            foreach ($this->workoutExerciseRecentSets($workoutExerciseId, $recentSets) as $row) {
                $keyboard[] = $row;
            }
        }

        if ($canRepeat) {
            $keyboard[] = [
                [
                    'text' => __('telegram.buttons.repeat_set'),
                    'callback_data' => 'set:repeat:'.$workoutExerciseId,
                ],
                [
                    'text' => __('telegram.buttons.complete_exercise'),
                    'callback_data' => 'exercise:back:current',
                ],
            ];
        } else {
            $keyboard[] = [
                [
                    'text' => __('telegram.buttons.complete_exercise'),
                    'callback_data' => 'exercise:back:current',
                ],
            ];
        }

        $keyboard[] = [
            [
                'text' => __('telegram.buttons.complete_workout'),
                'callback_data' => 'workout:complete:current',
            ],
            [
                'text' => __('telegram.buttons.back'),
                'callback_data' => 'workout:back:current',
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function workoutExerciseRecentSets(int $workoutExerciseId, array $recentSets): array
    {
        $rows = [];
        $row = [];

        foreach ($recentSets as $set) {
            $label = __('telegram.workout.last_result_value', [
                'weight' => number_format((float) $set['weight'], 1, '.', ' '),
                'repetitions' => (int) $set['repetitions'],
            ]);

            if (($set['count'] ?? 1) > 1) {
                $label .= ' '.__('telegram.workout.recent_set_count', ['count' => (int) $set['count']]);
            }

            $row[] = [
                'text' => $label,
                'callback_data' => 'set:quick:'.$workoutExerciseId.':'.number_format((float) $set['weight'], 2, '.', '').':'.(int) $set['repetitions'],
            ];

            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function setResult(int $workoutExerciseId): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.buttons.repeat_set'),
                        'callback_data' => 'set:repeat:'.$workoutExerciseId,
                    ],
                    [
                        'text' => __('telegram.buttons.new_set'),
                        'callback_data' => 'set:add:'.$workoutExerciseId,
                    ],
                ],
                [
                    [
                        'text' => __('telegram.buttons.complete_exercise'),
                        'callback_data' => 'exercise:back:current',
                    ],
                    [
                        'text' => __('telegram.buttons.complete_workout'),
                        'callback_data' => 'workout:complete:current',
                    ],
                ],
            ],
        ];
    }

    public function exerciseForecastActions(): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.buttons.back'),
                        'callback_data' => 'exercise:back:current',
                    ],
                ],
            ],
        ];
    }

    public function historyList(array $workouts): array
    {
        $keyboard = [];

        foreach ($workouts as $workout) {
            $keyboard[] = [
                [
                    'text' => $workout['label'],
                    'callback_data' => 'history:open:'.$workout['id'],
                ],
            ];
        }

        $keyboard[] = [
            [
                'text' => __('telegram.buttons.back'),
                'callback_data' => 'common:menu',
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function historyBack(): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.buttons.back'),
                        'callback_data' => 'history:list',
                    ],
                ],
            ],
        ];
    }

    public function recordsList(): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.buttons.back'),
                        'callback_data' => 'common:menu',
                    ],
                ],
            ],
        ];
    }

    public function goalsList(): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.buttons.add_goal'),
                        'callback_data' => 'goals:create',
                    ],
                ],
                [
                    [
                        'text' => __('telegram.buttons.back'),
                        'callback_data' => 'common:menu',
                    ],
                ],
            ],
        ];
    }

    public function settingsMenu(): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.buttons.back'),
                        'callback_data' => 'common:menu',
                    ],
                ],
            ],
        ];
    }

    public function adminMenu(): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.buttons.admin_groups'),
                        'callback_data' => 'admin:groups',
                    ],
                ],
                [
                    [
                        'text' => __('telegram.buttons.back'),
                        'callback_data' => 'common:menu',
                    ],
                ],
            ],
        ];
    }

    public function adminGroupsMenu(array $groups): array
    {
        $keyboard = [];

        foreach ($groups as $group) {
            $keyboard[] = [
                [
                    'text' => $group['name'].($group['count'] > 0 ? ' ('.$group['count'].')' : ''),
                    'callback_data' => 'admin:group:'.$group['id'],
                ],
            ];
        }

        $keyboard[] = [
            [
                'text' => __('telegram.buttons.back'),
                'callback_data' => 'admin:menu',
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function adminGroupActions(int $groupId, array $exercises): array
    {
        $keyboard = [];

        foreach ($exercises as $exercise) {
            $label = $exercise['name'];
            $statusMark = $exercise['is_active'] ? '✅' : '⛔';
            $mediaMark = $exercise['has_media']
                ? ($exercise['media_type'] === 'animation' ? '🎞' : '🖼')
                : '➕';

            $keyboard[] = [
                [
                    'text' => $statusMark.' '.$label,
                    'callback_data' => 'admin:toggle:'.$groupId.':'.$exercise['id'],
                ],
                [
                    'text' => $mediaMark,
                    'callback_data' => 'admin:media:'.$groupId.':'.$exercise['id'],
                ],
            ];
        }

        $keyboard[] = [
            [
                'text' => __('telegram.admin.add_exercise'),
                'callback_data' => 'admin:add:'.$groupId,
            ],
        ];

        $keyboard[] = [
            [
                'text' => __('telegram.buttons.back'),
                'callback_data' => 'admin:groups',
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function adminExerciseMediaActions(int $groupId, int $exerciseId): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.admin.media_photo'),
                        'callback_data' => 'admin:media_kind:'.$groupId.':'.$exerciseId.':photo',
                    ],
                    [
                        'text' => __('telegram.admin.media_gif'),
                        'callback_data' => 'admin:media_kind:'.$groupId.':'.$exerciseId.':animation',
                    ],
                ],
                [
                    [
                        'text' => __('telegram.buttons.back'),
                        'callback_data' => 'admin:group:'.$groupId,
                    ],
                    [
                        'text' => __('telegram.buttons.cancel'),
                        'callback_data' => 'common:cancel',
                    ],
                ],
            ],
        ];
    }

    public function adminExerciseCreateActions(int $groupId): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.buttons.back'),
                        'callback_data' => 'admin:group:'.$groupId,
                    ],
                    [
                        'text' => __('telegram.buttons.cancel'),
                        'callback_data' => 'common:cancel',
                    ],
                ],
            ],
        ];
    }

    public function templateManager(array $templates): array
    {
        $keyboard = [];

        foreach ($templates as $template) {
            $keyboard[] = [
                [
                    'text' => $template['name'].($template['count'] > 0 ? ' ('.$template['count'].')' : ''),
                    'callback_data' => 'templates:view:'.$template['id'],
                ],
            ];
        }

        $keyboard[] = [
            [
                'text' => __('telegram.templates.create'),
                'callback_data' => 'templates:create',
            ],
        ];

        $keyboard[] = [
            [
                'text' => __('telegram.templates.back_to_menu'),
                'callback_data' => 'common:menu',
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function templateDetailActions(int $templateId): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.templates.back_to_list'),
                        'callback_data' => 'templates:list',
                    ],
                    [
                        'text' => __('telegram.buttons.edit'),
                        'callback_data' => 'templates:edit:'.$templateId,
                    ],
                ],
                [
                    [
                        'text' => __('telegram.buttons.delete'),
                        'callback_data' => 'templates:delete:'.$templateId,
                    ],
                ],
                [
                    [
                        'text' => __('telegram.templates.back_to_menu'),
                        'callback_data' => 'common:menu',
                    ],
                ],
            ],
        ];
    }

    public function templateEditActions(int $templateId): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.templates.actions.name'),
                        'callback_data' => 'templates:edit_name:'.$templateId,
                    ],
                ],
                [
                    [
                        'text' => __('telegram.templates.actions.description'),
                        'callback_data' => 'templates:edit_description:'.$templateId,
                    ],
                ],
                [
                    [
                        'text' => __('telegram.templates.actions.day'),
                        'callback_data' => 'templates:edit_day:'.$templateId,
                    ],
                ],
                [
                    [
                        'text' => __('telegram.templates.actions.exercises'),
                        'callback_data' => 'templates:edit_exercises:'.$templateId,
                    ],
                ],
                [
                    [
                        'text' => __('telegram.templates.actions.delete'),
                        'callback_data' => 'templates:delete:'.$templateId,
                    ],
                ],
                [
                    [
                        'text' => __('telegram.buttons.back'),
                        'callback_data' => 'templates:view:'.$templateId,
                    ],
                ],
            ],
        ];
    }

    public function templateExerciseToggleActions(int $templateId, array $exercises, array $selectedExerciseIds): array
    {
        $keyboard = [];

        $selected = array_map('intval', $selectedExerciseIds);

        $row = [];

        foreach ($exercises as $exercise) {
            $isSelected = in_array((int) $exercise['id'], $selected, true);

            $row[] = [
                'text' => ($isSelected ? __('telegram.templates.selected_mark').' ' : '').$exercise['name'],
                'callback_data' => 'templates:exercise_toggle:'.$templateId.':'.$exercise['id'],
            ];

            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $keyboard[] = $row;
        }

        $keyboard[] = [
            [
                'text' => __('telegram.buttons.back'),
                'callback_data' => 'templates:edit:'.$templateId,
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function templateCreationExerciseSelection(array $exercises, array $selectedExerciseIds): array
    {
        $keyboard = [];
        $selected = array_map('intval', $selectedExerciseIds);
        $row = [];

        foreach ($exercises as $exercise) {
            $isSelected = in_array((int) $exercise['id'], $selected, true);

            $row[] = [
                'text' => ($isSelected ? __('telegram.templates.selected_mark').' ' : '').$exercise['name'],
                'callback_data' => 'templates:create_exercise:'.$exercise['id'],
            ];

            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $keyboard[] = $row;
        }

        $keyboard[] = [
            [
                'text' => __('telegram.templates.back_to_groups'),
                'callback_data' => 'templates:back',
            ],
            [
                'text' => __('telegram.templates.done'),
                'callback_data' => 'templates:done',
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function templateDeleteConfirmActions(int $templateId): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.templates.actions.confirm_delete'),
                        'callback_data' => 'templates:delete_confirm:'.$templateId,
                    ],
                    [
                        'text' => __('telegram.buttons.back'),
                        'callback_data' => 'templates:view:'.$templateId,
                    ],
                ],
            ],
        ];
    }

    public function templateGroupSelection(array $groups, array $selectedGroupIds): array
    {
        $selected = array_map('intval', $selectedGroupIds);
        $keyboard = [];

        $keyboard[] = [
            [
                'text' => __('telegram.templates.preset_chest_triceps'),
                'callback_data' => 'templates:split:chest_triceps',
            ],
            [
                'text' => __('telegram.templates.preset_back_biceps'),
                'callback_data' => 'templates:split:back_biceps',
            ],
        ];

        $keyboard[] = [
            [
                'text' => __('telegram.templates.preset_back_legs'),
                'callback_data' => 'templates:split:back_legs',
            ],
            [
                'text' => __('telegram.templates.preset_push'),
                'callback_data' => 'templates:split:push',
            ],
        ];

        $keyboard[] = [
            [
                'text' => __('telegram.templates.preset_pull'),
                'callback_data' => 'templates:split:pull',
            ],
            [
                'text' => __('telegram.templates.preset_full_body'),
                'callback_data' => 'templates:split:full_body',
            ],
        ];

        $row = [];

        foreach ($groups as $group) {
            $isSelected = in_array((int) $group['id'], $selected, true);

            $row[] = [
                'text' => ($isSelected ? __('telegram.templates.selected_mark').' ' : '').$group['name'],
                'callback_data' => 'templates:group:'.$group['id'],
            ];

            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $keyboard[] = $row;
        }

        $keyboard[] = [
            [
                'text' => __('telegram.templates.done'),
                'callback_data' => 'templates:done',
            ],
            [
                'text' => __('telegram.buttons.back'),
                'callback_data' => 'templates:back',
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function templateDayOfWeekSelection(?int $selectedDayOfWeek, string $backCallback, string $actionPrefix = 'templates:day_create', ?int $templateId = null): array
    {
        $selected = $selectedDayOfWeek ?? 0;
        $keyboard = [];

        $days = [
            1 => __('telegram.days.monday'),
            2 => __('telegram.days.tuesday'),
            3 => __('telegram.days.wednesday'),
            4 => __('telegram.days.thursday'),
            5 => __('telegram.days.friday'),
            6 => __('telegram.days.saturday'),
            7 => __('telegram.days.sunday'),
        ];

        $row = [];

        foreach ($days as $value => $label) {
            $callbackData = $actionPrefix === 'templates:day_edit' && $templateId !== null
                ? $actionPrefix.':'.$templateId.':'.$value
                : $actionPrefix.':'.$value;

            $row[] = [
                'text' => ($selected === $value ? __('telegram.templates.selected_mark').' ' : '').$label,
                'callback_data' => $callbackData,
            ];

            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $keyboard[] = $row;
        }

        $keyboard[] = [
            [
                'text' => ($selected === 0 ? __('telegram.templates.selected_mark').' ' : '').__('telegram.templates.day_none'),
                'callback_data' => $actionPrefix === 'templates:day_edit' && $templateId !== null
                    ? $actionPrefix.':'.$templateId.':0'
                    : $actionPrefix.':0',
            ],
        ];

        $keyboard[] = [
            [
                'text' => __('telegram.buttons.back'),
                'callback_data' => $backCallback,
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function goalTypeSelection(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => __('telegram.goals.types.target_weight'), 'callback_data' => 'goals:type:target_weight'],
                ],
                [
                    ['text' => __('telegram.goals.types.target_one_rep_max'), 'callback_data' => 'goals:type:target_one_rep_max'],
                ],
                [
                    ['text' => __('telegram.goals.types.target_body_weight'), 'callback_data' => 'goals:type:target_body_weight'],
                ],
                [
                    ['text' => __('telegram.goals.types.weekly_workouts'), 'callback_data' => 'goals:type:weekly_workouts'],
                ],
                [
                    ['text' => __('telegram.buttons.back'), 'callback_data' => 'goals:list'],
                ],
            ],
        ];
    }
}
