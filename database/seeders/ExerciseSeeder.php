<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Жим штанги лёжа', 'group' => 'Грудь'],
            ['name' => 'Приседания со штангой', 'group' => 'Ноги'],
            ['name' => 'Становая тяга', 'group' => 'Спина'],
            ['name' => 'Жим ногами', 'group' => 'Ноги'],
            ['name' => 'Жим гантелей лёжа', 'group' => 'Грудь'],
            ['name' => 'Тяга верхнего блока', 'group' => 'Спина'],
            ['name' => 'Подтягивания', 'group' => 'Спина'],
            ['name' => 'Тяга штанги в наклоне', 'group' => 'Спина'],
            ['name' => 'Жим штанги над головой', 'group' => 'Плечи'],
            ['name' => 'Сгибания рук со штангой', 'group' => 'Бицепс'],
            ['name' => 'Разгибания рук на блоке', 'group' => 'Трицепс'],
            ['name' => 'Разведение гантелей', 'group' => 'Плечи'],
            ['name' => 'Подъём гантелей на бицепс', 'group' => 'Бицепс'],
            ['name' => 'Французский жим', 'group' => 'Трицепс'],
            ['name' => 'Разгибание ног', 'group' => 'Ноги'],
            ['name' => 'Сгибание ног', 'group' => 'Ноги'],
            ['name' => 'Подъёмы на носки', 'group' => 'Икры'],
            ['name' => 'Скручивания', 'group' => 'Пресс'],
            ['name' => 'Планка', 'group' => 'Пресс'],
        ];

        foreach ($items as $item) {
            $muscleGroup = MuscleGroup::query()->where('slug', Str::slug($item['group']))->first();

            if (! $muscleGroup) {
                continue;
            }

            Exercise::query()->updateOrCreate(
                [
                    'user_id' => null,
                    'slug' => Str::slug($item['name']),
                ],
                [
                    'muscle_group_id' => $muscleGroup->id,
                    'name' => $item['name'],
                    'description' => null,
                    'is_custom' => false,
                    'is_active' => true,
                ]
            );
        }
    }
}
