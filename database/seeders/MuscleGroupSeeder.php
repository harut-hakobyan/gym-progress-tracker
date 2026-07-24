<?php

namespace Database\Seeders;

use App\Models\MuscleGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MuscleGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'Грудь',
            'Спина',
            'Ноги',
            'Плечи',
            'Бицепс',
            'Трицепс',
            'Пресс',
            'Ягодицы',
            'Икры',
            'Предплечья',
            'Кардио',
            'Другое',
        ];

        foreach ($groups as $name) {
            MuscleGroup::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
