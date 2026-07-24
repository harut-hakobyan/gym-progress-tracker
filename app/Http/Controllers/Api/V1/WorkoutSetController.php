<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkoutSetUpdateRequest;
use App\Http\Resources\Api\V1\WorkoutSetResource;
use App\Models\WorkoutSet;
use App\Services\Records\PersonalRecordService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class WorkoutSetController extends Controller
{
    public function __construct(
        private readonly PersonalRecordService $records,
    ) {
    }

    public function update(WorkoutSetUpdateRequest $request, WorkoutSet $workoutSet): WorkoutSetResource
    {
        $this->authorize('update', $workoutSet);

        $workoutSet->update([
            'weight' => $request->input('weight', $workoutSet->weight),
            'repetitions' => $request->input('repetitions', $workoutSet->repetitions),
            'duration_seconds' => $request->input('duration_seconds', $workoutSet->duration_seconds),
            'distance_meters' => $request->input('distance_meters', $workoutSet->distance_meters),
            'rpe' => $request->input('rpe', $workoutSet->rpe),
            'rir' => $request->input('rir', $workoutSet->rir),
            'rest_seconds' => $request->input('rest_seconds', $workoutSet->rest_seconds),
            'notes' => $request->input('notes', $workoutSet->notes),
        ]);

        $exercise = $workoutSet->workoutExercise->exercise;
        if ($exercise !== null) {
            $this->records->rebuildForExercise($workoutSet->workoutExercise->workout->user, $exercise);
        }

        return new WorkoutSetResource($workoutSet->refresh());
    }

    public function destroy(WorkoutSet $workoutSet): JsonResponse
    {
        $this->authorize('delete', $workoutSet);

        $exercise = $workoutSet->workoutExercise->exercise;
        $user = $workoutSet->workoutExercise->workout->user;

        $workoutSet->delete();

        if ($exercise !== null) {
            $this->records->rebuildForExercise($user, $exercise);
        }

        return response()->noContent();
    }
}
