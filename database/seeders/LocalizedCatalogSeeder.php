<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\ExerciseTranslation;
use App\Models\MuscleGroup;
use App\Models\MuscleGroupTranslation;
use Illuminate\Database\Seeder;

class LocalizedCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->muscleGroups() as $baseName => $translations) {
            $group = MuscleGroup::query()->where('name', $baseName)->first();

            if ($group === null) {
                continue;
            }

            foreach ($translations as $locale => $name) {
                MuscleGroupTranslation::query()->updateOrCreate(
                    [
                        'muscle_group_id' => $group->id,
                        'locale' => $locale,
                    ],
                    [
                        'name' => $name,
                    ]
                );
            }
        }

        foreach ($this->exercises() as $baseName => $translations) {
            $exercise = Exercise::query()->where('name', $baseName)->whereNull('user_id')->first();

            if ($exercise === null) {
                continue;
            }

            foreach ($translations as $locale => $data) {
                ExerciseTranslation::query()->updateOrCreate(
                    [
                        'exercise_id' => $exercise->id,
                        'locale' => $locale,
                    ],
                    [
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                    ]
                );
            }
        }
    }

    private function muscleGroups(): array
    {
        return [
            'Грудь' => [
                'en' => 'Chest',
                'hy' => 'Կրծքավանդակ',
            ],
            'Спина' => [
                'en' => 'Back',
                'hy' => 'Մեջք',
            ],
            'Ноги' => [
                'en' => 'Legs',
                'hy' => 'Ոտքեր',
            ],
            'Плечи' => [
                'en' => 'Shoulders',
                'hy' => 'Ուսեր',
            ],
            'Бицепс' => [
                'en' => 'Biceps',
                'hy' => 'Բիցեպս',
            ],
            'Трицепс' => [
                'en' => 'Triceps',
                'hy' => 'Տրիցեպս',
            ],
            'Пресс' => [
                'en' => 'Abs',
                'hy' => 'Որովայն',
            ],
            'Ягодицы' => [
                'en' => 'Glutes',
                'hy' => 'Հետույք',
            ],
            'Икры' => [
                'en' => 'Calves',
                'hy' => 'Սրունքներ',
            ],
            'Предплечья' => [
                'en' => 'Forearms',
                'hy' => 'Նախաբազուկներ',
            ],
            'Кардио' => [
                'en' => 'Cardio',
                'hy' => 'Կարդիո',
            ],
            'Другое' => [
                'en' => 'Other',
                'hy' => 'Այլ',
            ],
        ];
    }

    private function exercises(): array
    {
        return [
            'Жим штанги лёжа' => [
                'en' => ['name' => 'Barbell Bench Press'],
                'hy' => ['name' => 'Հորիզոնական նստարանին ծանրաձողի սեղմում'],
            ],
            'Приседания со штангой' => [
                'en' => ['name' => 'Barbell Squat'],
                'hy' => ['name' => 'Ծանրաձողով կքանիստ'],
            ],
            'Становая тяга' => [
                'en' => ['name' => 'Deadlift'],
                'hy' => ['name' => 'Մահացու ձգում'],
            ],
            'Жим ногами' => [
                'en' => ['name' => 'Leg Press'],
                'hy' => ['name' => 'Ոտքերի մամլիչ'],
            ],
            'Жим гантелей лёжа' => [
                'en' => ['name' => 'Dumbbell Bench Press'],
                'hy' => ['name' => 'Հորիզոնական նստարանին դամբելների սեղմում'],
            ],
            'Тяга верхнего блока' => [
                'en' => ['name' => 'Lat Pulldown'],
                'hy' => ['name' => 'Վերին բլոկի ձգում'],
            ],
            'Подтягивания' => [
                'en' => ['name' => 'Pull-Up'],
                'hy' => ['name' => 'Ձգումներ'],
            ],
            'Тяга штанги в наклоне' => [
                'en' => ['name' => 'Bent-Over Barbell Row'],
                'hy' => ['name' => 'Թեքված դիրքով ծանրաձողի ձգում'],
            ],
            'Жим штанги над головой' => [
                'en' => ['name' => 'Overhead Barbell Press'],
                'hy' => ['name' => 'Ծանրաձողի սեղմում գլխավերևում'],
            ],
            'Сгибания рук со штангой' => [
                'en' => ['name' => 'Barbell Curl'],
                'hy' => ['name' => 'Ծանրաձողով բիցեպսի ծալում'],
            ],
            'Разгибания рук на блоке' => [
                'en' => ['name' => 'Cable Triceps Pushdown'],
                'hy' => ['name' => 'Բլոկով տրիցեպսի ձգում ներքև'],
            ],
            'Разведение гантелей' => [
                'en' => ['name' => 'Dumbbell Lateral Raise'],
                'hy' => ['name' => 'Դամբելների կողմային բարձրացում'],
            ],
            'Подъём гантелей на бицепс' => [
                'en' => ['name' => 'Dumbbell Biceps Curl'],
                'hy' => ['name' => 'Դամբելներով բիցեպսի ծալում'],
            ],
            'Французский жим' => [
                'en' => ['name' => 'Skull Crusher'],
                'hy' => ['name' => 'Ֆրանսիական սեղմում'],
            ],
            'Разгибание ног' => [
                'en' => ['name' => 'Leg Extension'],
                'hy' => ['name' => 'Ոտքերի ծալում-ձգում'],
            ],
            'Сгибание ног' => [
                'en' => ['name' => 'Leg Curl'],
                'hy' => ['name' => 'Ոտքերի ծալում'],
            ],
            'Подъёмы на носки' => [
                'en' => ['name' => 'Calf Raise'],
                'hy' => ['name' => 'Սրունքների բարձրացում'],
            ],
            'Скручивания' => [
                'en' => ['name' => 'Crunch'],
                'hy' => ['name' => 'Որովայնի ոլորումներ'],
            ],
            'Планка' => [
                'en' => ['name' => 'Plank'],
                'hy' => ['name' => 'Պլանկա'],
            ],
        ];
    }
}
