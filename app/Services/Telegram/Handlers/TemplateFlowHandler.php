<?php

namespace App\Services\Telegram\Handlers;

use App\Enums\TelegramState;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Models\UserTelegramState;
use App\Models\WorkoutTemplate;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramKeyboardFactory;
use App\Services\Telegram\TelegramStateService;
use App\Services\Templates\WorkoutTemplateService;
use App\Services\Workouts\WorkoutFlowService;

class TemplateFlowHandler
{
    public function __construct(
        private readonly WorkoutTemplateService $templates,
        private readonly WorkoutFlowService $workouts,
        private readonly TelegramStateService $stateService,
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
    ) {
    }

    public function showTemplateList(User $user, int $chatId, int $messageId, ?string $headline = null): void
    {
        $templates = $user->workoutTemplates()
            ->withCount('templateExercises')
            ->latest()
            ->get()
            ->map(fn (WorkoutTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'count' => (int) $template->template_exercises_count,
            ])
            ->all();

        $text = $headline ?? __('telegram.templates.title');

        if ($templates === []) {
            $text .= "\n\n".__('telegram.templates.empty');
        } else {
            $text .= "\n\n";

            foreach ($templates as $template) {
                $text .= __('telegram.templates.list_item', ['name' => $template['name'], 'count' => $template['count']])."\n";
            }
        }

        $this->bot->editMessageText($chatId, $messageId, $text, [
            'reply_markup' => $this->keyboards->templateManager($templates),
        ]);
    }

    public function startCreation(User $user, int $chatId, int $messageId): void
    {
        $this->stateService->put($user, TelegramState::AwaitingTemplateName, [
            'message_id' => $messageId,
        ]);

        $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.name_prompt'), [
            'reply_markup' => $this->keyboards->cancelOnly(),
        ]);
    }

    public function handle(User $user, array $message, UserTelegramState $state): void
    {
        $text = trim((string) data_get($message, 'text', ''));
        $chatId = (int) data_get($message, 'chat.id');

        if ($state->state === TelegramState::AwaitingTemplateName->value) {
            $this->handleName($user, $chatId, $text, $state);

            return;
        }

        if ($state->state === TelegramState::AwaitingTemplateDayOfWeek->value) {
            $this->bot->sendMessage($chatId, __('telegram.templates.choose_day_reminder'), [
                'reply_markup' => $this->keyboards->cancelOnly(),
            ]);

            return;
        }

        if ($state->state === TelegramState::AwaitingTemplateRename->value) {
            $this->handleRename($user, $chatId, $text, $state);

            return;
        }

        if ($state->state === TelegramState::AwaitingTemplateDescription->value) {
            $this->handleDescription($user, $chatId, $text, $state);

            return;
        }

        if ($state->state === TelegramState::AwaitingTemplateMuscleGroups->value) {
            $this->bot->sendMessage($chatId, __('telegram.templates.choose_groups_reminder'), [
                'reply_markup' => $this->keyboards->cancelOnly(),
            ]);
        }
    }

    public function handleCallbacks(string $callbackQueryId, User $user, int $chatId, int $messageId, string $action, ?string $target, ?string $tail): void
    {
        if ($action === 'list') {
            $this->stateService->forget($user);
            $this->showTemplateList($user, $chatId, $messageId);

            return;
        }

        if ($action === 'create') {
            $this->startCreation($user, $chatId, $messageId);

            return;
        }

        if ($action === 'view' && $target !== null) {
            $this->showTemplate($user, $chatId, $messageId, (int) $target);

            return;
        }

        if ($action === 'edit' && $target !== null) {
            $this->showEditMenu($user, $chatId, $messageId, (int) $target);

            return;
        }

        if ($action === 'edit_name' && $target !== null) {
            $this->startRename($user, $chatId, $messageId, (int) $target);

            return;
        }

        if ($action === 'edit_description' && $target !== null) {
            $this->startDescription($user, $chatId, $messageId, (int) $target);

            return;
        }

        if ($action === 'edit_day' && $target !== null) {
            $this->startDayEdit($user, $chatId, $messageId, (int) $target);

            return;
        }

        if ($action === 'edit_exercises' && $target !== null) {
            $this->showExerciseManager($user, $chatId, $messageId, (int) $target);

            return;
        }

        if ($action === 'exercise_toggle' && $target !== null && $tail !== null) {
            $this->toggleExerciseInTemplate($user, $chatId, $messageId, (int) $target, (int) $tail);

            return;
        }

        if ($action === 'delete' && $target !== null) {
            $this->showDeleteConfirm($user, $chatId, $messageId, (int) $target);

            return;
        }

        if ($action === 'delete_confirm' && $target !== null) {
            $this->deleteTemplate($user, $chatId, $messageId, (int) $target);

            return;
        }

        if ($action === 'day_create' && $target !== null) {
            $this->handleDaySelection($user, $chatId, $messageId, (int) $target);

            return;
        }

        if ($action === 'day_select' && $target !== null) {
            $this->handleDaySelection($user, $chatId, $messageId, (int) $target);

            return;
        }

        if ($action === 'day_edit' && $target !== null && $tail !== null) {
            $this->updateTemplateDayOfWeek($user, $chatId, $messageId, (int) $target, (int) $tail);

            return;
        }

        if ($action === 'group' && $target !== null) {
            $this->toggleGroup($user, $chatId, $messageId, (int) $target);

            return;
        }

        if ($action === 'split' && $target !== null) {
            $this->applyPreset($user, $chatId, $messageId, $target);

            return;
        }

        if ($action === 'done') {
            $this->completeCreation($user, $chatId, $messageId);

            return;
        }

        if ($action === 'back') {
            $this->handleBack($user, $chatId, $messageId);

            return;
        }
    }

    private function handleName(User $user, int $chatId, string $text, UserTelegramState $state): void
    {
        $name = $this->templates->normalizeName($text);

        if ($name === '') {
            $this->bot->sendMessage($chatId, __('telegram.templates.invalid_name'), [
                'reply_markup' => $this->keyboards->cancelOnly(),
            ]);

            return;
        }

        $this->stateService->put($user, TelegramState::AwaitingTemplateDayOfWeek, [
            'message_id' => (int) data_get($state->payload, 'message_id'),
            'template_name' => $name,
            'day_of_week' => null,
            'selected_group_ids' => [],
        ]);

        $this->showDaySelection($chatId, (int) data_get($state->payload, 'message_id'), $name, null, false, null);
    }

    private function handleDaySelection(User $user, int $chatId, int $messageId, int $dayOfWeek): void
    {
        $state = $this->stateService->get($user);

        if ($state === null || $state->state !== TelegramState::AwaitingTemplateDayOfWeek->value) {
            return;
        }

        $dayOfWeek = $this->normalizeDayOfWeek($dayOfWeek);
        $name = (string) data_get($state->payload, 'template_name', '');

        $this->stateService->put($user, TelegramState::AwaitingTemplateMuscleGroups, [
            'message_id' => (int) data_get($state->payload, 'message_id', $messageId),
            'template_name' => $name,
            'day_of_week' => $dayOfWeek,
            'selected_group_ids' => [],
        ]);

        $this->showGroupSelection($chatId, (int) data_get($state->payload, 'message_id', $messageId), $name, [], $dayOfWeek);
    }

    private function handleBack(User $user, int $chatId, int $messageId): void
    {
        $state = $this->stateService->get($user);

        if ($state !== null && $state->state === TelegramState::AwaitingTemplateMuscleGroups->value) {
            $name = (string) data_get($state->payload, 'template_name', '');
            $dayOfWeek = $this->normalizeDayOfWeek(data_get($state->payload, 'day_of_week'));

            $this->stateService->put($user, TelegramState::AwaitingTemplateDayOfWeek, [
                'message_id' => (int) data_get($state->payload, 'message_id', $messageId),
                'template_name' => $name,
                'day_of_week' => $dayOfWeek,
            ]);

            $this->showDaySelection($chatId, (int) data_get($state->payload, 'message_id', $messageId), $name, $dayOfWeek, false, null);

            return;
        }

        if ($state !== null && $state->state === TelegramState::AwaitingTemplateDayOfWeek->value) {
            $this->stateService->put($user, TelegramState::AwaitingTemplateName, [
                'message_id' => (int) data_get($state->payload, 'message_id', $messageId),
            ]);

            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.name_prompt'), [
                'reply_markup' => $this->keyboards->cancelOnly(),
            ]);

            return;
        }

        $this->showTemplateList($user, $chatId, $messageId);
    }

    private function toggleGroup(User $user, int $chatId, int $messageId, int $groupId): void
    {
        $state = $this->stateService->get($user);

        if ($state === null || $state->state !== TelegramState::AwaitingTemplateMuscleGroups->value) {
            return;
        }

        $selected = collect(data_get($state->payload, 'selected_group_ids', []))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (in_array($groupId, $selected, true)) {
            $selected = array_values(array_filter($selected, fn (int $id) => $id !== $groupId));
        } else {
            $selected[] = $groupId;
        }

        $name = (string) data_get($state->payload, 'template_name', '');
        $dayOfWeek = $this->normalizeDayOfWeek(data_get($state->payload, 'day_of_week'));

        $this->stateService->put($user, TelegramState::AwaitingTemplateMuscleGroups, [
            'message_id' => (int) data_get($state->payload, 'message_id', $messageId),
            'template_name' => $name,
            'day_of_week' => $dayOfWeek,
            'selected_group_ids' => $selected,
        ]);

        $this->showGroupSelection($chatId, (int) data_get($state->payload, 'message_id', $messageId), $name, $selected, $dayOfWeek);
    }

    private function applyPreset(User $user, int $chatId, int $messageId, string $preset): void
    {
        $state = $this->stateService->get($user);

        if ($state === null || $state->state !== TelegramState::AwaitingTemplateMuscleGroups->value) {
            return;
        }

        $groupNames = $this->presetGroupNames($preset);

        if ($groupNames === []) {
            $this->bot->sendMessage($chatId, __('telegram.unknown_action'));

            return;
        }

        $selected = MuscleGroup::query()
            ->whereIn('name', $groupNames)
            ->pluck('id')
            ->all();

        $name = (string) data_get($state->payload, 'template_name', '');
        $dayOfWeek = $this->normalizeDayOfWeek(data_get($state->payload, 'day_of_week'));

        $this->stateService->put($user, TelegramState::AwaitingTemplateMuscleGroups, [
            'message_id' => (int) data_get($state->payload, 'message_id', $messageId),
            'template_name' => $name,
            'day_of_week' => $dayOfWeek,
            'selected_group_ids' => $selected,
        ]);

        $this->showGroupSelection($chatId, (int) data_get($state->payload, 'message_id', $messageId), $name, $selected, $dayOfWeek);
    }

    private function completeCreation(User $user, int $chatId, int $messageId): void
    {
        $state = $this->stateService->get($user);

        if ($state === null || $state->state !== TelegramState::AwaitingTemplateMuscleGroups->value) {
            return;
        }

        $selectedGroupIds = collect(data_get($state->payload, 'selected_group_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        if ($selectedGroupIds === []) {
            $this->bot->sendMessage($chatId, __('telegram.templates.no_groups_selected'));

            return;
        }

        $template = $this->templates->createFromMuscleGroups(
            $user,
            (string) data_get($state->payload, 'template_name', 'Workout'),
            $selectedGroupIds,
            null,
            $this->normalizeDayOfWeek(data_get($state->payload, 'day_of_week')),
        );

        $template->loadMissing('templateExercises.exercise.muscleGroup');

        $this->stateService->forget($user);

        $summary = __('telegram.templates.created', [
            'name' => $template->name,
            'exercise_count' => $template->templateExercises->count(),
        ]);

        $this->showTemplateList($user, $chatId, $messageId, $summary);
    }

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

        $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.rename_prompt'), [
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

        $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.description_prompt'), [
            'reply_markup' => $this->keyboards->cancelOnly(),
        ]);
    }

    private function startDayEdit(User $user, int $chatId, int $messageId, int $templateId): void
    {
        $template = $user->workoutTemplates()->find($templateId);

        if ($template === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $this->bot->editMessageText($chatId, $messageId, $this->daySelectionText($template->name, $template->day_of_week), [
            'reply_markup' => $this->keyboards->templateDayOfWeekSelection(
                $template->day_of_week,
                'templates:edit:'.$template->id,
                'templates:day_edit',
                $template->id
            ),
        ]);
    }

    private function updateTemplateDayOfWeek(User $user, int $chatId, int $messageId, int $templateId, int $dayOfWeek): void
    {
        $template = $user->workoutTemplates()->find($templateId);

        if ($template === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $template = $this->templates->updateTemplate($template, [
            'day_of_week' => $this->normalizeDayOfWeek($dayOfWeek),
        ]);

        $this->showEditMenu($user, $chatId, $messageId, $template->id);
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

        $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.delete_confirm', ['name' => $template->name]), [
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

        $this->showTemplateList($user, $chatId, $messageId, __('telegram.templates.deleted', ['name' => $name]));
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
            __('telegram.templates.edit_title'),
            $template->name,
            __('telegram.templates.day_summary', ['day' => $this->dayOfWeekLabel($template->day_of_week)]),
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
            __('telegram.templates.exercises_title'),
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
            $lines[] = __('telegram.templates.no_exercises');
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
    {
        $template = $user->workoutTemplates()
            ->with(['templateExercises.exercise.muscleGroup'])
            ->find($templateId);

        if ($template === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.templates.not_found'), [
                'reply_markup' => $this->keyboards->templateManager([]),
            ]);

            return;
        }

        $lines = [
            __('telegram.templates.title'),
            '',
            $template->name,
            __('telegram.templates.day_summary', ['day' => $this->dayOfWeekLabel($template->day_of_week)]),
        ];

        if ($template->description !== null && $template->description !== '') {
            $lines[] = $template->description;
        }

        $lines[] = '';
        $lines[] = __('telegram.templates.exercise_count', ['count' => $template->templateExercises->count()]);

        foreach ($template->templateExercises as $templateExercise) {
            $lines[] = __('telegram.templates.exercise_row', ['name' => $templateExercise->exercise->name, 'group' => $templateExercise->exercise->muscleGroup->name]);
        }

        $this->bot->editMessageText($chatId, $messageId, implode("\n", $lines), [
            'reply_markup' => $this->keyboards->templateDetailActions($template->id),
        ]);
    }

    private function showGroupSelection(int $chatId, int $messageId, string $name, array $selectedGroupIds, ?int $dayOfWeek = null): void
    {
        $groups = MuscleGroup::query()
            ->orderBy('name')
            ->get()
            ->map(fn (MuscleGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
            ])
            ->all();

        $selectedNames = MuscleGroup::query()
            ->whereIn('id', $selectedGroupIds)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $text = __('telegram.templates.choose_groups', ['name' => $name]);
        $text .= "\n\n".__('telegram.templates.day_summary', ['day' => $this->dayOfWeekLabel($dayOfWeek)]);

        if ($selectedNames !== []) {
            $text .= "\n\n".__('telegram.templates.selected_groups', [
                'groups' => implode(', ', $selectedNames),
            ]);
        }

        $this->bot->editMessageText($chatId, $messageId, $text, [
            'reply_markup' => $this->keyboards->templateGroupSelection($groups, $selectedGroupIds),
        ]);
    }

    private function showDaySelection(int $chatId, int $messageId, string $name, ?int $selectedDayOfWeek, bool $editing, ?int $templateId): void
    {
        $backCallback = $editing && $templateId !== null ? 'templates:edit:'.$templateId : 'templates:back';
        $actionPrefix = $editing && $templateId !== null ? 'templates:day_edit' : 'templates:day_create';

        $this->bot->editMessageText($chatId, $messageId, $this->daySelectionText($name, $selectedDayOfWeek), [
            'reply_markup' => $this->keyboards->templateDayOfWeekSelection($selectedDayOfWeek, $backCallback, $actionPrefix, $templateId),
        ]);
    }

    private function daySelectionText(string $name, ?int $selectedDayOfWeek): string
    {
        $lines = [
            __('telegram.templates.day_prompt', ['name' => $name]),
            __('telegram.templates.choose_day_reminder'),
        ];

        if ($selectedDayOfWeek !== null) {
            $lines[] = __('telegram.templates.day_summary', ['day' => $this->dayOfWeekLabel($selectedDayOfWeek)]);
        }

        return implode("\n\n", $lines);
    }

    private function normalizeDayOfWeek(mixed $dayOfWeek): ?int
    {
        if ($dayOfWeek === null || $dayOfWeek === '') {
            return null;
        }

        $dayOfWeek = (int) $dayOfWeek;

        if ($dayOfWeek < 1 || $dayOfWeek > 7) {
            return null;
        }

        return $dayOfWeek;
    }

    private function dayOfWeekLabel(?int $dayOfWeek): string
    {
        return match ($this->normalizeDayOfWeek($dayOfWeek)) {
            1 => __('telegram.days.monday'),
            2 => __('telegram.days.tuesday'),
            3 => __('telegram.days.wednesday'),
            4 => __('telegram.days.thursday'),
            5 => __('telegram.days.friday'),
            6 => __('telegram.days.saturday'),
            7 => __('telegram.days.sunday'),
            default => __('telegram.templates.day_not_set'),
        };
    }

    private function presetGroupNames(string $preset): array
    {
        return match ($preset) {
            'chest_triceps' => ['Грудь', 'Трицепс'],
            'back_biceps' => ['Спина', 'Бицепс'],
            'back_legs' => ['Спина', 'Ноги'],
            'push' => ['Грудь', 'Плечи', 'Трицепс'],
            'pull' => ['Спина', 'Бицепс', 'Предплечья'],
            'legs' => ['Ноги', 'Ягодицы', 'Икры'],
            'full_body' => ['Грудь', 'Спина', 'Ноги', 'Плечи', 'Бицепс', 'Трицепс', 'Пресс'],
            default => [],
        };
    }
}
