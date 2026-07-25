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

class TemplateFlowHandler
{
    public function __construct(
        private readonly WorkoutTemplateService $templates,
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
                $text .= '• '.$template['name'].' — '.$template['count']."\n";
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

        return;
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

        $this->stateService->put($user, TelegramState::AwaitingTemplateMuscleGroups, [
            'message_id' => (int) data_get($state->payload, 'message_id'),
            'template_name' => $name,
            'selected_group_ids' => [],
        ]);

        $this->showGroupSelection($chatId, (int) data_get($state->payload, 'message_id'), $name, []);
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

        $this->stateService->put($user, TelegramState::AwaitingTemplateMuscleGroups, [
            'message_id' => (int) data_get($state->payload, 'message_id', $messageId),
            'template_name' => $name,
            'selected_group_ids' => $selected,
        ]);

        $this->showGroupSelection($chatId, (int) data_get($state->payload, 'message_id', $messageId), $name, $selected);
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

        $this->stateService->put($user, TelegramState::AwaitingTemplateMuscleGroups, [
            'message_id' => (int) data_get($state->payload, 'message_id', $messageId),
            'template_name' => $name,
            'selected_group_ids' => $selected,
        ]);

        $this->showGroupSelection($chatId, (int) data_get($state->payload, 'message_id', $messageId), $name, $selected);
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
        );

        $template->loadMissing('templateExercises.exercise.muscleGroup');

        $this->stateService->forget($user);

        $summary = __('telegram.templates.created', [
            'name' => $template->name,
            'exercise_count' => $template->templateExercises->count(),
        ]);

        $this->showTemplateList($user, $chatId, $messageId, $summary);
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
        ];

        if ($template->description !== null && $template->description !== '') {
            $lines[] = $template->description;
        }

        $lines[] = '';
        $lines[] = __('telegram.templates.exercise_count', ['count' => $template->templateExercises->count()]);

        foreach ($template->templateExercises as $templateExercise) {
            $lines[] = '• '.$templateExercise->exercise->name.' — '.$templateExercise->exercise->muscleGroup->name;
        }

        $this->bot->editMessageText($chatId, $messageId, implode("\n", $lines), [
            'reply_markup' => $this->keyboards->templateDetailActions(),
        ]);
    }

    private function showGroupSelection(int $chatId, int $messageId, string $name, array $selectedGroupIds): void
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

        if ($selectedNames !== []) {
            $text .= "\n\n".__('telegram.templates.selected_groups', [
                'groups' => implode(', ', $selectedNames),
            ]);
        }

        $this->bot->editMessageText($chatId, $messageId, $text, [
            'reply_markup' => $this->keyboards->templateGroupSelection($groups, $selectedGroupIds),
        ]);
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
