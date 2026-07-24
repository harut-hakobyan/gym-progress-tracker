<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkoutTemplateExerciseStoreRequest;
use App\Http\Requests\Api\V1\WorkoutTemplateExerciseUpdateRequest;
use App\Http\Requests\Api\V1\WorkoutTemplateReorderRequest;
use App\Http\Resources\Api\V1\WorkoutTemplateExerciseResource;
use App\Models\Exercise;
use App\Models\WorkoutTemplate;
use App\Models\WorkoutTemplateExercise;
use App\Services\Templates\WorkoutTemplateService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class WorkoutTemplateExerciseController extends Controller
{
    public function __construct(
        private readonly WorkoutTemplateService $templates,
    ) {
    }

    public function store(WorkoutTemplateExerciseStoreRequest $request, WorkoutTemplate $workoutTemplate): JsonResponse
    {
        $this->authorize('update', $workoutTemplate);

        $exercise = Exercise::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $request->user()->id))
            ->findOrFail($request->integer('exercise_id'));

        $templateExercise = $this->templates->addExercise($workoutTemplate, $exercise, $request->validated());

        return response()->json([
            'data' => new WorkoutTemplateExerciseResource($templateExercise->loadMissing('exercise')),
        ], Response::HTTP_CREATED);
    }

    public function update(WorkoutTemplateExerciseUpdateRequest $request, WorkoutTemplateExercise $workoutTemplateExercise): WorkoutTemplateExerciseResource
    {
        $this->authorize('update', $workoutTemplateExercise->workoutTemplate);

        $templateExercise = $this->templates->updateExercise($workoutTemplateExercise, $request->validated());

        return new WorkoutTemplateExerciseResource($templateExercise->loadMissing('exercise'));
    }

    public function destroy(WorkoutTemplateExercise $workoutTemplateExercise): JsonResponse
    {
        $this->authorize('delete', $workoutTemplateExercise->workoutTemplate);

        $this->templates->removeExercise($workoutTemplateExercise);

        return response()->noContent();
    }

    public function reorder(WorkoutTemplateReorderRequest $request, WorkoutTemplate $workoutTemplate): JsonResponse
    {
        $this->authorize('update', $workoutTemplate);

        $this->templates->reorder($workoutTemplate, $request->validated('exercise_ids'));

        return response()->json([
            'data' => WorkoutTemplateExerciseResource::collection(
                $workoutTemplate->refresh()->loadMissing('templateExercises.exercise')->templateExercises
            ),
        ]);
    }
}
