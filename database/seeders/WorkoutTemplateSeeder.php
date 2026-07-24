<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\WorkoutTemplate;
use App\Models\WorkoutTemplateExercise;
use Illuminate\Database\Seeder;

class WorkoutTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'Грудь и трицепс' => [
                'Жим штанги лёжа',
                'Жим гантелей лёжа',
                'Разведение гантелей',
                'Разгибания рук на блоке',
                'Французский жим',
            ],
            'Спина и бицепс' => [
                'Тяга верхнего блока',
                'Подтягивания',
                'Тяга штанги в наклоне',
                'Сгибания рук со штангой',
                'Подъём гантелей на бицепс',
            ],
            'Ноги' => [
                'Приседания со штангой',
                'Жим ногами',
                'Разгибание ног',
                'Сгибание ног',
                'Подъёмы на носки',
            ],
            'Плечи' => [
                'Жим штанги над головой',
                'Разведение гантелей',
                'Подъём гантелей на бицепс',
            ],
            'Full Body' => [
                'Приседания со штангой',
                'Жим штанги лёжа',
                'Тяга штанги в наклоне',
                'Жим штанги над головой',
                'Планка',
            ],
        ];

        foreach ($templates as $templateName => $exerciseNames) {
            $template = WorkoutTemplate::query()->updateOrCreate(
                ['user_id' => null, 'name' => $templateName],
                ['description' => null, 'is_active' => true]
            );

            $position = 1;

            foreach ($exerciseNames as $exerciseName) {
                $exercise = Exercise::query()->where('name', $exerciseName)->whereNull('user_id')->first();

                if ($exercise === null) {
                    continue;
                }

                WorkoutTemplateExercise::query()->updateOrCreate(
                    [
                        'workout_template_id' => $template->id,
                        'exercise_id' => $exercise->id,
                    ],
                    [
                        'position' => $position++,
                        'target_sets' => 3,
                        'target_repetitions_min' => 6,
                        'target_repetitions_max' => 10,
                        'target_weight' => null,
                        'rest_seconds' => 90,
                        'notes' => null,
                    ]
                );
            }
        }
    }
}
