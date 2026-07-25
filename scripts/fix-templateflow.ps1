$ErrorActionPreference = 'Stop'

$path = Join-Path $PSScriptRoot '..\app\Services\Telegram\Handlers\TemplateFlowHandler.php'
$path = [System.IO.Path]::GetFullPath($path)

$text = [System.IO.File]::ReadAllText($path, [System.Text.Encoding]::UTF8)

$pattern = @'
(?s)    private function startRename\(User \$user, int \$chatId, int \$messageId, int \$templateId\): void\r?\n    \{.*?    private function showTemplate\(User \$user, int \$chatId, int \$messageId, int \$templateId\): void
'@

$replacement = @'
    private function startRename(User $user, int $chatId, int $messageId, int $templateId): void
    {
        $template = $user->workoutTemplates()->find($templateId);

        if ($template === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $this->stateService->put($user, TelegramState::AwaitingTemplateRename, [
            'message_id' => $messageId,
            'template_id' => $template->id,
        ]);

        $this->bot->editMessageText($chatId, $messageId, 'Введите новое название шаблона:', [
            'reply_markup' => $this->keyboards->cancelOnly(),
        ]);
    }

    private function startDescription(User $user, int $chatId, int $messageId, int $templateId): void
    {
        $template = $user->workoutTemplates()->find($templateId);

        if ($template === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $this->stateService->put($user, TelegramState::AwaitingTemplateDescription, [
            'message_id' => $messageId,
            'template_id' => $template->id,
        ]);

        $this->bot->editMessageText($chatId, $messageId, 'Введите новое описание шаблона или "-" чтобы очистить:', [
            'reply_markup' => $this->keyboards->cancelOnly(),
        ]);
    }

    private function handleRename(User $user, int $chatId, string $text, UserTelegramState $state): void
    {
        $templateId = (int) data_get($state->payload, 'template_id');
        $template = $user->workoutTemplates()->find($templateId);

        if ($template === null) {
            $this->stateService->forget($user);
            $this->bot->sendMessage($chatId, __('telegram.templates.not_found'));

            return;
        }

        $name = $this->templates->normalizeName($text);

        if ($name === '') {
            $this->bot->sendMessage($chatId, __('telegram.templates.invalid_name'), [
                'reply_markup' => $this->keyboards->cancelOnly(),
            ]);

            return;
        }

        $template = $this->templates->updateTemplate($template, [
            'name' => $name,
        ]);

        $this->stateService->forget($user);

        $this->showEditMenu($user, $chatId, (int) data_get($state->payload, 'message_id'), $template->id);
    }

    private function handleDescription(User $user, int $chatId, string $text, UserTelegramState $state): void
    {
        $templateId = (int) data_get($state->payload, 'template_id');
        $template = $user->workoutTemplates()->find($templateId);

        if ($template === null) {
            $this->stateService->forget($user);
            $this->bot->sendMessage($chatId, __('telegram.templates.not_found'));

            return;
        }

        $description = trim($text);
        $description = $description === '-' ? null : $description;
        $description = $description === '' ? null : $description;

        $template = $this->templates->updateDescription($template, $description);

        $this->stateService->forget($user);

        $this->showEditMenu($user, $chatId, (int) data_get($state->payload, 'message_id'), $template->id);
    }

    private function showDeleteConfirm(User $user, int $chatId, int $messageId, int $templateId): void
    {
        $template = $user->workoutTemplates()->find($templateId);

        if ($template === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $this->bot->editMessageText($chatId, $messageId, 'Удалить шаблон «'.$template->name.'»?', [
            'reply_markup' => $this->keyboards->templateDeleteConfirmActions($template->id),
        ]);
    }

    private function deleteTemplate(User $user, int $chatId, int $messageId, int $templateId): void
    {
        $template = $user->workoutTemplates()->find($templateId);

        if ($template === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $name = $template->name;
        $this->templates->deleteTemplate($template);
        $this->stateService->forget($user);

        $this->showTemplateList($user, $chatId, $messageId, 'Шаблон удалён: '.$name);
    }

    private function showEditMenu(User $user, int $chatId, int $messageId, int $templateId): void
    {
        $template = $user->workoutTemplates()
            ->withCount('templateExercises')
            ->find($templateId);

        if ($template === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $lines = [
            'Редактирование шаблона',
            '',
            $template->name,
            __('telegram.templates.exercise_count', ['count' => $template->template_exercises_count]),
        ];

        if ($template->description !== null && $template->description !== '') {
            $lines[] = $template->description;
        }

        $this->bot->editMessageText($chatId, $messageId, implode("\n", $lines), [
            'reply_markup' => $this->keyboards->templateEditActions($template->id),
        ]);
    }

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

        if ($template->templateExercises->isEmpty()) {
            $lines[] = '';
            $lines[] = 'Пока упражнений нет.';
        } else {
            $lines[] = '';

            foreach ($template->templateExercises->sortBy('position') as $templateExercise) {
                $lines[] = '• '.$templateExercise->exercise->name;
            }
        }

        $templateExercises = $template->templateExercises
            ->sortBy('position')
            ->map(fn ($templateExercise) => [
                'id' => $templateExercise->id,
                'name' => $templateExercise->exercise->name,
            ])
            ->all();

        $this->bot->editMessageText($chatId, $messageId, implode("\n", $lines), [
            'reply_markup' => $this->keyboards->templateExercisesActions($template->id, $templateExercises),
        ]);
    }

    private function showExerciseSelection(User $user, int $chatId, int $messageId, int $templateId): void
    {
        $template = $user->workoutTemplates()->find($templateId);

        if ($template === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $exercises = $this->workouts->availableExercises($user)
            ->map(fn ($exercise) => [
                'id' => $exercise->id,
                'name' => $exercise->name,
            ])
            ->all();

        $this->bot->editMessageText($chatId, $messageId, 'Выберите упражнение для добавления:', [
            'reply_markup' => $this->keyboards->templateExerciseSelectionActions($template->id, $exercises),
        ]);
    }

    private function addExerciseToTemplate(User $user, int $chatId, int $messageId, int $templateId, int $exerciseId): void
    {
        $template = $user->workoutTemplates()->find($templateId);
        $exercise = $this->workouts->exerciseForUser($user, $exerciseId);

        if ($template === null || $exercise === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $this->templates->addExercise($template, $exercise);

        $this->showExerciseManager($user, $chatId, $messageId, $template->id);
    }

    private function removeExerciseFromTemplate(User $user, int $chatId, int $messageId, int $templateId, int $templateExerciseId): void
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

        $templateExercise = $template->templateExercises->firstWhere('id', $templateExerciseId);

        if ($templateExercise === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $this->templates->removeExercise($templateExercise);

        $this->showExerciseManager($user, $chatId, $messageId, $template->id);
    }

    private function showTemplate(User $user, int $chatId, int $messageId, int $templateId): void
'@

$newText = [regex]::Replace($text, $pattern, $replacement, 1)
if ($newText -eq $text) {
    throw 'Replacement failed'
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($path, $newText, $utf8NoBom)
