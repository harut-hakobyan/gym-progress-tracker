<?php

namespace App\Services\Telegram;

class TelegramKeyboardFactory
{
    public function mainMenu(): array
    {
        return [
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
    }

    public function cancelOnly(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '❌ Отмена', 'callback_data' => 'common:cancel'],
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
                'text' => 'Пустая тренировка',
                'callback_data' => 'workout:template:empty',
            ],
        ];

        $keyboard[] = [
            [
                'text' => '⬅️ Назад',
                'callback_data' => 'common:menu',
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
                'text' => '🏁 Завершить тренировку',
                'callback_data' => 'workout:complete:current',
            ],
            [
                'text' => '⬅️ Назад',
                'callback_data' => 'workout:back:current',
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function workoutExerciseActions(int $workoutExerciseId, int $exerciseId, bool $canRepeat = false): array
    {
        $keyboard = [
            [
                [
                    'text' => '➕ Добавить подход',
                    'callback_data' => 'set:add:'.$workoutExerciseId,
                ],
            ],
            [
                [
                    'text' => '📈 Прогресс',
                    'callback_data' => 'exercise:progress:'.$exerciseId,
                ],
            ],
        ];

        if ($canRepeat) {
            $keyboard[] = [
                [
                    'text' => '🔁 Повторить подход',
                    'callback_data' => 'set:repeat:'.$workoutExerciseId,
                ],
                [
                    'text' => '✅ Завершить упражнение',
                    'callback_data' => 'exercise:back:current',
                ],
            ];
        } else {
            $keyboard[] = [
                [
                    'text' => '✅ Завершить упражнение',
                    'callback_data' => 'exercise:back:current',
                ],
            ];
        }

        $keyboard[] = [
            [
                'text' => '🏁 Завершить тренировку',
                'callback_data' => 'workout:complete:current',
            ],
            [
                'text' => '⬅️ Назад',
                'callback_data' => 'workout:back:current',
            ],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    public function setResult(int $workoutExerciseId): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🔁 Повторить подход',
                        'callback_data' => 'set:repeat:'.$workoutExerciseId,
                    ],
                    [
                        'text' => '➕ Новый подход',
                        'callback_data' => 'set:add:'.$workoutExerciseId,
                    ],
                ],
                [
                    [
                        'text' => '✅ Завершить упражнение',
                        'callback_data' => 'exercise:back:current',
                    ],
                    [
                        'text' => '🏁 Завершить тренировку',
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
                        'text' => '⬅️ Назад',
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
                'text' => '⬅️ Назад',
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
                        'text' => '⬅️ Назад',
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
                        'text' => '⬅️ Назад',
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
                        'text' => '➕ Добавить цель',
                        'callback_data' => 'goals:create',
                    ],
                ],
                [
                    [
                        'text' => '⬅️ Назад',
                        'callback_data' => 'common:menu',
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

    public function templateDetailActions(): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => __('telegram.templates.back_to_list'),
                        'callback_data' => 'templates:list',
                    ],
                    [
                        'text' => __('telegram.templates.create'),
                        'callback_data' => 'templates:create',
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
                'text' => ($isSelected ? '✅ ' : '').$group['name'],
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
                'text' => __('telegram.templates.back_to_list'),
                'callback_data' => 'templates:list',
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
                    ['text' => '⬅️ Назад', 'callback_data' => 'goals:list'],
                ],
            ],
        ];
    }
}
