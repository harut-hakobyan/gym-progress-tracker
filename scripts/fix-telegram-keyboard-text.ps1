Param(
    [string]$Path = 'app\Services\Telegram\TelegramKeyboardFactory.php'
)

$fullPath = Resolve-Path $Path
$lines = [System.Collections.Generic.List[string]]::new()
$lines.AddRange([string[]](Get-Content $fullPath))

$replacements = @{
    35  = "                    ['text' => '❌ Отмена', 'callback_data' => 'common:cancel'],"
    60  = "                'text' => 'Стандартные шаблоны',"
    67  = "                'text' => '⬅️ Назад',"
    90  = "                'text' => 'Пустая тренировка',"
    94  = "                'text' => '⬅️ Назад',"
    126 = "                'text' => '🏁 Завершить тренировку',"
    130 = "                'text' => '⬅️ Назад',"
    143 = "                    'text' => '➕ Добавить подход',"
    149 = "                    'text' => '📈 Прогресс',"
    158 = "                    'text' => '🔁 Повторить подход',"
    162 = "                    'text' => '✅ Завершить упражнение',"
    169 = "                    'text' => '✅ Завершить упражнение',"
    177 = "                'text' => '🏁 Завершить тренировку',"
    181 = "                'text' => '⬅️ Назад',"
    195 = "                        'text' => '🔁 Повторить подход',"
    199 = "                        'text' => '➕ Новый подход',"
    205 = "                        'text' => '✅ Завершить упражнение',"
    209 = "                        'text' => '🏁 Завершить тренировку',"
    223 = "                        'text' => '⬅️ Назад',"
    246 = "                'text' => '⬅️ Назад',"
    260 = "                        'text' => '⬅️ Назад',"
    274 = "                        'text' => '⬅️ Назад',"
    288 = "                        'text' => '➕ Добавить цель',"
    294 = "                        'text' => '⬅️ Назад',"
    342 = "                        'text' => '✏️ Редактировать',"
    348 = "                        'text' => '🗑 Удалить',"
    368 = "                        'text' => '📝 Название шаблона',"
    374 = "                        'text' => '📄 Описание',"
    380 = "                        'text' => '🏋️ Упражнения',"
    386 = "                        'text' => '🗑 Удалить шаблон',"
    392 = "                        'text' => '⬅️ Назад',"
    412 = "                'text' => (`$isSelected ? '✅ ' : '').`$exercise['name'],"
    428 = "                'text' => '⬅️ Назад',"
    442 = "                        'text' => 'Да, удалить',"
    446 = "                        'text' => '⬅️ Назад',"
    498 = "                'text' => (`$isSelected ? '✅ ' : '').`$group['name'],"
    543 = "                    ['text' => '⬅️ Назад', 'callback_data' => 'goals:list'],"
}

foreach ($index in $replacements.Keys) {
    $lines[$index - 1] = $replacements[$index]
}

[System.IO.File]::WriteAllLines($fullPath, $lines, [System.Text.UTF8Encoding]::new($false))
