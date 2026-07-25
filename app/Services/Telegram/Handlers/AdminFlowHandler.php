<?php

namespace App\Services\Telegram\Handlers;

use App\Enums\TelegramState;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Models\UserTelegramState;
use App\Services\Telegram\TelegramAccessService;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramKeyboardFactory;
use App\Services\Telegram\TelegramStateService;
use Illuminate\Support\Str;

class AdminFlowHandler
{
    public function __construct(
        private readonly TelegramAccessService $access,
        private readonly TelegramStateService $stateService,
        private readonly TelegramBotService $bot,
        private readonly TelegramKeyboardFactory $keyboards,
    ) {
    }

    public function showSettingsMenu(User $user, int $chatId, ?int $messageId = null): void
    {
        $text = __('telegram.settings.title');
        $text .= "\n\n".__('telegram.settings.basic');

        $replyMarkup = ['reply_markup' => $this->keyboards->settingsMenu()];

        if ($messageId === null) {
            $this->bot->sendMessage($chatId, $text, $replyMarkup);

            return;
        }

        $this->bot->editMessageText($chatId, $messageId, $text, $replyMarkup);
    }

    public function handle(User $user, array $message, UserTelegramState $state): void
    {
        if (! $this->access->isAdmin($user)) {
            return;
        }

        if ($state->state === TelegramState::AwaitingAdminExerciseName->value) {
            $this->handleExerciseName($user, $message, $state);

            return;
        }

        if ($state->state === TelegramState::AwaitingAdminExerciseMedia->value) {
            $this->handleExerciseMedia($user, $message, $state);

            return;
        }
    }

    private function handleExerciseName(User $user, array $message, UserTelegramState $state): void
    {
        $name = trim((string) data_get($message, 'text', ''));
        $chatId = (int) data_get($message, 'chat.id');
        $groupId = (int) data_get($state->payload, 'group_id');
        $messageId = (int) data_get($state->payload, 'message_id');

        if ($name === '') {
            $this->bot->sendMessage($chatId, __('telegram.admin.invalid_exercise_name'));

            return;
        }

        $group = MuscleGroup::query()->find($groupId);

        if ($group === null) {
            $this->stateService->forget($user);
            $this->bot->sendMessage($chatId, __('telegram.admin.group_not_found'));

            return;
        }

        $exercise = Exercise::query()->updateOrCreate(
            [
                'user_id' => null,
                'muscle_group_id' => $group->id,
                'slug' => Str::slug($name),
            ],
            [
                'muscle_group_id' => $group->id,
                'name' => $name,
                'description' => null,
                'media_type' => null,
                'media_value' => null,
                'is_custom' => false,
                'is_active' => true,
            ]
        );

        $this->stateService->forget($user);

        $this->showGroup($user, $chatId, $messageId, $group->id, __('telegram.admin.exercise_created', ['name' => $exercise->name]));
    }

    private function handleExerciseMedia(User $user, array $message, UserTelegramState $state): void
    {
        $chatId = (int) data_get($message, 'chat.id');
        $groupId = (int) data_get($state->payload, 'group_id');
        $exerciseId = (int) data_get($state->payload, 'exercise_id');
        $messageId = (int) data_get($state->payload, 'message_id');
        $kind = (string) data_get($state->payload, 'kind', '');

        $group = MuscleGroup::query()->find($groupId);
        $exercise = Exercise::query()->find($exerciseId);

        if ($group === null || $exercise === null || (int) $exercise->muscle_group_id !== $group->id) {
            $this->stateService->forget($user);
            $this->bot->sendMessage($chatId, __('telegram.admin.group_not_found'));

            return;
        }

        $mediaValue = $this->extractMediaValue($message, $kind);

        if ($mediaValue === null) {
            $this->bot->sendMessage($chatId, __('telegram.admin.invalid_media'));

            return;
        }

        $exercise->update([
            'media_type' => $this->resolveMediaType($message, $kind),
            'media_value' => $mediaValue,
        ]);

        $this->stateService->forget($user);

        $this->showGroup($user, $chatId, $messageId, $group->id, __('telegram.admin.media_saved', ['name' => $exercise->name]));
    }

    public function showAdminMenu(User $user, int $chatId, int $messageId): void
    {
        if (! $this->access->isAdmin($user)) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.admin.no_access'), [
                'reply_markup' => $this->keyboards->settingsMenu(),
            ]);

            return;
        }

        $text = __('telegram.admin.home_title')."\n\n".__('telegram.admin.home_hint');

        $this->bot->editMessageText($chatId, $messageId, $text, [
            'reply_markup' => $this->keyboards->adminMenu(),
        ]);
    }

    public function showAdminGroupsMenu(User $user, int $chatId, int $messageId): void
    {
        if (! $this->access->isAdmin($user)) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.admin.no_access'), [
                'reply_markup' => $this->keyboards->settingsMenu(),
            ]);

            return;
        }

        $groups = MuscleGroup::query()
            ->withCount(['exercises as active_exercises_count' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get()
            ->map(fn (MuscleGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'count' => (int) $group->active_exercises_count,
            ])
            ->all();

        $text = __('telegram.admin.groups_title')."\n\n".__('telegram.admin.groups_hint');

        $this->bot->editMessageText($chatId, $messageId, $text, [
            'reply_markup' => $this->keyboards->adminGroupsMenu($groups),
        ]);
    }

    public function showGroup(User $user, int $chatId, int $messageId, int $groupId, ?string $headline = null): void
    {
        if (! $this->access->isAdmin($user)) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.admin.no_access'), [
                'reply_markup' => $this->keyboards->settingsMenu(),
            ]);

            return;
        }

        $group = MuscleGroup::query()->find($groupId);

        if ($group === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.admin.group_not_found'), [
                'reply_markup' => $this->keyboards->adminGroupsMenu([]),
            ]);

            return;
        }

        $exercises = Exercise::query()
            ->where('muscle_group_id', $group->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Exercise $exercise) => [
                'id' => $exercise->id,
                'name' => $exercise->name,
                'is_active' => (bool) $exercise->is_active,
                'has_media' => $exercise->media_value !== null && $exercise->media_value !== '',
                'media_type' => $exercise->media_type,
            ])
            ->all();

        $text = $headline ?? __('telegram.admin.group_title', ['name' => $group->name]);
        $text .= "\n\n".__('telegram.admin.group_hint');

        if ($exercises === []) {
            $text .= "\n\n".__('telegram.admin.no_exercises');
        }

        $this->bot->editMessageText($chatId, $messageId, $text, [
            'reply_markup' => $this->keyboards->adminGroupActions($group->id, $exercises),
        ]);
    }

    public function showExerciseMediaChoice(User $user, int $chatId, int $messageId, int $groupId, int $exerciseId): void
    {
        if (! $this->access->isAdmin($user)) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.admin.no_access'), [
                'reply_markup' => $this->keyboards->settingsMenu(),
            ]);

            return;
        }

        $group = MuscleGroup::query()->find($groupId);
        $exercise = Exercise::query()->find($exerciseId);

        if ($group === null || $exercise === null || (int) $exercise->muscle_group_id !== $group->id) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.admin.group_not_found'), [
                'reply_markup' => $this->keyboards->adminGroupsMenu([]),
            ]);

            return;
        }

        $text = __('telegram.admin.media_prompt', ['name' => $exercise->name]);

        if ($exercise->media_value !== null && $exercise->media_value !== '') {
            $text .= "\n\n".__('telegram.admin.media_current', [
                'type' => $exercise->media_type === 'animation' ? __('telegram.admin.media_gif_short') : __('telegram.admin.media_photo_short'),
            ]);
        }

        $this->bot->editMessageText($chatId, $messageId, $text, [
            'reply_markup' => $this->keyboards->adminExerciseMediaActions($group->id, $exercise->id),
        ]);
    }

    public function startExerciseCreate(User $user, int $chatId, int $messageId, int $groupId): void
    {
        if (! $this->access->isAdmin($user)) {
            return;
        }

        $group = MuscleGroup::query()->find($groupId);

        if ($group === null) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.admin.group_not_found'), [
                'reply_markup' => $this->keyboards->adminGroupsMenu([]),
            ]);

            return;
        }

        $this->stateService->put($user, TelegramState::AwaitingAdminExerciseName, [
            'message_id' => $messageId,
            'group_id' => $group->id,
        ]);

        $this->bot->editMessageText($chatId, $messageId, __('telegram.admin.enter_exercise_name', ['name' => $group->name]), [
            'reply_markup' => $this->keyboards->adminExerciseCreateActions($group->id),
        ]);
    }

    public function startExerciseMedia(User $user, int $chatId, int $messageId, int $groupId, int $exerciseId, string $kind): void
    {
        if (! $this->access->isAdmin($user)) {
            return;
        }

        $group = MuscleGroup::query()->find($groupId);
        $exercise = Exercise::query()->find($exerciseId);

        if ($group === null || $exercise === null || (int) $exercise->muscle_group_id !== $group->id) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.admin.group_not_found'), [
                'reply_markup' => $this->keyboards->adminGroupsMenu([]),
            ]);

            return;
        }

        $this->stateService->put($user, TelegramState::AwaitingAdminExerciseMedia, [
            'message_id' => $messageId,
            'group_id' => $group->id,
            'exercise_id' => $exercise->id,
            'kind' => $kind,
        ]);

        $this->bot->editMessageText($chatId, $messageId, __('telegram.admin.media_input_prompt', [
            'name' => $exercise->name,
            'type' => $kind === 'animation' ? __('telegram.admin.media_gif_short') : __('telegram.admin.media_photo_short'),
        ]), [
            'reply_markup' => $this->keyboards->adminExerciseMediaActions($group->id, $exercise->id),
        ]);
    }

    public function toggleExercise(User $user, int $chatId, int $messageId, int $groupId, int $exerciseId): void
    {
        if (! $this->access->isAdmin($user)) {
            return;
        }

        $group = MuscleGroup::query()->find($groupId);
        $exercise = Exercise::query()->find($exerciseId);

        if ($group === null || $exercise === null || (int) $exercise->muscle_group_id !== $group->id) {
            $this->bot->editMessageText($chatId, $messageId, __('telegram.admin.group_not_found'), [
                'reply_markup' => $this->keyboards->adminGroupsMenu([]),
            ]);

            return;
        }

        $exercise->update([
            'is_active' => ! $exercise->is_active,
        ]);

        $this->showGroup($user, $chatId, $messageId, $group->id, __('telegram.admin.exercise_toggled', ['name' => $exercise->name]));
    }

    private function extractMediaValue(array $message, string $kind): ?string
    {
        $text = trim((string) data_get($message, 'text', ''));

        if ($text !== '') {
            return $text;
        }

        if ($kind === 'animation') {
            return data_get($message, 'animation.file_id');
        }

        $photo = data_get($message, 'photo');

        if (is_array($photo) && $photo !== []) {
            $last = end($photo);

            return is_array($last) ? (string) data_get($last, 'file_id') : null;
        }

        return null;
    }

    private function resolveMediaType(array $message, string $kind): string
    {
        if (trim((string) data_get($message, 'text', '')) !== '') {
            return $kind;
        }

        if (data_get($message, 'animation.file_id') !== null) {
            return 'animation';
        }

        return 'photo';
    }
}
