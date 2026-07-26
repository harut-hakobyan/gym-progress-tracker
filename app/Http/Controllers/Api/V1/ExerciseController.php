<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ExerciseStoreRequest;
use App\Http\Requests\Api\V1\ExerciseUpdateRequest;
use App\Http\Resources\Api\V1\ExerciseResource;
use App\Models\Exercise;
use App\Services\Exercises\ExerciseTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ExerciseController extends Controller
{
    public function __construct(
        private readonly ExerciseTranslationService $translations,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $exercises = Exercise::query()
            ->with(['muscleGroup.translations', 'translations'])
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
            ->get()
            ->sortBy(fn (Exercise $exercise) => [strtolower($exercise->muscleGroup?->name ?? ''), strtolower($exercise->name)])
            ->values();

        return response()->json([
            'data' => ExerciseResource::collection($exercises),
        ]);
    }

    public function store(ExerciseStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Exercise::class);

        $user = $request->user();
        $exercise = Exercise::query()->create([
            'user_id' => $user->id,
            'muscle_group_id' => $request->integer('muscle_group_id'),
            'name' => $request->string('name')->toString(),
            'slug' => $request->filled('slug') ? $request->string('slug')->toString() : Str::slug($request->string('name')->toString()),
            'description' => $request->input('description'),
            'media_type' => $request->input('media_type'),
            'media_value' => $request->input('media_value'),
            'is_custom' => (bool) $request->boolean('is_custom', true),
            'is_active' => (bool) $request->boolean('is_active', true),
        ]);

        $this->translations->syncTranslations($exercise, (array) $request->input('translations', []));

        $exercise->load(['muscleGroup.translations', 'translations']);

        return response()->json([
            'data' => new ExerciseResource($exercise),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Exercise $exercise): ExerciseResource
    {
        $this->authorize('view', $exercise);

        return new ExerciseResource($exercise->load(['muscleGroup.translations', 'translations']));
    }

    public function update(ExerciseUpdateRequest $request, Exercise $exercise): ExerciseResource
    {
        $this->authorize('update', $exercise);

        $exercise->update([
            'muscle_group_id' => $request->integer('muscle_group_id', $exercise->muscle_group_id),
            'name' => $request->filled('name') ? $request->string('name')->toString() : $exercise->name,
            'slug' => $request->filled('slug') ? $request->string('slug')->toString() : $exercise->slug,
            'description' => $request->input('description', $exercise->description),
            'media_type' => $request->input('media_type', $exercise->media_type),
            'media_value' => $request->input('media_value', $exercise->media_value),
            'is_custom' => $request->has('is_custom') ? (bool) $request->boolean('is_custom') : $exercise->is_custom,
            'is_active' => $request->has('is_active') ? (bool) $request->boolean('is_active') : $exercise->is_active,
        ]);

        $this->translations->syncTranslations($exercise, (array) $request->input('translations', []));

        return new ExerciseResource($exercise->load(['muscleGroup.translations', 'translations']));
    }

    public function destroy(Request $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('delete', $exercise);

        $exercise->delete();

        return response()->noContent();
    }
}
