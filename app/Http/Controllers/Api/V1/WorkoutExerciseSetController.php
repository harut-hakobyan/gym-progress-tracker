<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkoutSetStoreRequest;
use App\Http\Resources\Api\V1\WorkoutSetResource;
use App\Models\WorkoutExercise;
use App\Services\Records\PersonalRecordService;
use App\Services\Workouts\WorkoutFlowService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class WorkoutExerciseSetController extends Controller
{
    public function __construct(
        private readonly WorkoutFlowService $workouts,
        private readonly PersonalRecordService $records,
    ) {
    }

    public function store(WorkoutSetStoreRequest $request, WorkoutExercise $workoutExercise): JsonResponse
    {
        $this->authorize('update', $workoutExercise->workout);

        $set = $this->workouts->addSet(
            $workoutExercise,
            (float) $request->input('weight'),
            (int) $request->input('repetitions'),
            $request->integer('rpe'),
            $request->integer('rir'),
            $request->filled('type') ? \App\Enums\WorkoutSetType::tryFrom($request->string('type')->toString()) ?? \App\Enums\WorkoutSetType::Working : \App\Enums\WorkoutSetType::Working,
            $request->integer('rest_seconds'),
            $request->input('notes')
        );

        $this->records->syncFromWorkoutSet($set);

        return response()->json([
            'data' => new WorkoutSetResource($set),
        ], Response::HTTP_CREATED);
    }
}
