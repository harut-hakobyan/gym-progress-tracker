$ErrorActionPreference = 'Stop'

$path = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\app\Services\Telegram\Handlers\TemplateFlowHandler.php'))
$text = [System.IO.File]::ReadAllText($path, [System.Text.Encoding]::UTF8)

$text = $text -replace [regex]::Escape("        if (`$action === 'exercise_add' && `$target !== null) {`r`n            `$this->showExerciseSelection(`$user, `$chatId, `$messageId, (int) `$target);`r`n`r`n            return;`r`n        }`r`n`r`n        if (`$action === 'exercise_pick' && `$target !== null && `$tail !== null) {`r`n            `$this->addExerciseToTemplate(`$user, `$chatId, `$messageId, (int) `$target, (int) `$tail);`r`n`r`n            return;`r`n        }`r`n`r`n        if (`$action === 'exercise_remove' && `$target !== null && `$tail !== null) {`r`n            `$this->removeExerciseFromTemplate(`$user, `$chatId, `$messageId, (int) `$target, (int) `$tail);`r`n`r`n            return;`r`n        }`r`n"),
    "        if (`$action === 'exercise_toggle' && `$target !== null && `$tail !== null) {`r`n            `$this->toggleExerciseInTemplate(`$user, `$chatId, `$messageId, (int) `$target, (int) `$tail);`r`n`r`n            return;`r`n        }`r`n"

$blockPattern = '(?s)    private function showExerciseManager\(User \$user, int \$chatId, int \$messageId, int \$templateId\): void\r?\n    \{.*?    private function showTemplate\(User \$user, int \$chatId, int \$messageId, int \$templateId\): void'
$blockReplacement = @'
    private function showExerciseManager(User $user, int $chatId, int $messageId, int $templateId): void
    {
        $template = $user->workoutTemplates()
            ->with(['templateExercises.exercise'])
            ->find($templateId);

        if ($template === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $lines = [
            'Упражнения шаблона',
            '',
            $template->name,
        ];

        $selectedExerciseIds = $template->templateExercises
            ->pluck('exercise_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $exercises = $this->workouts->availableExercises($user)
            ->map(fn ($exercise) => [
                'id' => $exercise->id,
                'name' => $exercise->name,
            ])
            ->all();

        if ($selectedExerciseIds === []) {
            $lines[] = '';
            $lines[] = 'Пока упражнений нет.';
        }

        $this->bot->editMessageText($chatId, $messageId, implode("\n", $lines), [
            'reply_markup' => $this->keyboards->templateExerciseToggleActions($template->id, $exercises, $selectedExerciseIds),
        ]);
    }

    private function toggleExerciseInTemplate(User $user, int $chatId, int $messageId, int $templateId, int $exerciseId): void
    {
        $template = $user->workoutTemplates()
            ->with(['templateExercises.exercise'])
            ->find($templateId);
        $exercise = $this->workouts->exerciseForUser($user, $exerciseId);

        if ($template === null || $exercise === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $templateExercise = $template->templateExercises->firstWhere('exercise_id', $exercise->id);

        if ($templateExercise !== null) {
            $this->templates->removeExercise($templateExercise);
        } else {
            $this->templates->addExercise($template, $exercise);
        }

        $this->showExerciseManager($user, $chatId, $messageId, $template->id);
    }

    private function showTemplate(User $user, int $chatId, int $messageId, int $templateId): void
'@

$newText = [regex]::Replace($text, $blockPattern, $blockReplacement, 1)
if ($newText -eq $text) {
    throw 'Block replacement failed'
}

[System.IO.File]::WriteAllText($path, $newText, (New-Object System.Text.UTF8Encoding($false)))
