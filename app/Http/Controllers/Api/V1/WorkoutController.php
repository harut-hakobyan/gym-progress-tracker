<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WorkoutStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkoutExerciseStoreRequest;
use App\Http\Requests\Api\V1\WorkoutStoreRequest;
use App\Http\Requests\Api\V1\WorkoutUpdateRequest;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\Exercise;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutTemplate;
use App\Services\Records\PersonalRecordService;
use App\Services\Workouts\WorkoutFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WorkoutController extends Controller
{
    public function __construct(
        private readonly WorkoutFlowService $workouts,
        private readonly PersonalRecordService $records,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $workouts = Workout::query()
            ->with(['workoutExercises.exercise.translations', 'workoutExercises.exercise.muscleGroup.translations', 'workoutExercises.sets'])
            ->where('user_id', $request->user()->id)
            ->latest('started_at')
            ->paginate(15);

        return response()->json([
            'data' => WorkoutResource::collection($workouts),
            'meta' => [
                'current_page' => $workouts->currentPage(),
                'last_page' => $workouts->lastPage(),
                'total' => $workouts->total(),
            ],
        ]);
    }

    public function store(WorkoutStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Workout::class);

        $user = $request->user();
        $template = null;

        if ($request->filled('workout_template_id')) {
            $template = WorkoutTemplate::query()->where('user_id', $user->id)->findOrFail($request->integer('workout_template_id'));
        }

        $workout = Workout::query()->create([
            'user_id' => $user->id,
            'workout_template_id' => $template?->id,
            'name' => $request->input('name', $template?->name ?? 'Workout'),
            'status' => WorkoutStatus::Draft,
            'started_at' => null,
            'completed_at' => null,
            'duration_seconds' => null,
            'user_body_weight' => $request->input('user_body_weight'),
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'data' => new WorkoutResource($workout),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Workout $workout): WorkoutResource
    {
        $this->authorize('view', $workout);

        return new WorkoutResource($workout->loadMissing(['workoutExercises.exercise.translations', 'workoutExercises.exercise.muscleGroup.translations', 'workoutExercises.sets']));
    }

    public function update(WorkoutUpdateRequest $request, Workout $workout): WorkoutResource
    {
        $this->authorize('update', $workout);

        $workout->update([
            'name' => $request->input('name', $workout->name),
            'user_body_weight' => $request->filled('user_body_weight') ? $request->input('user_body_weight') : $workout->user_body_weight,
            'notes' => $request->input('notes', $workout->notes),
        ]);

        return new WorkoutResource($workout->refresh()->loadMissing(['workoutExercises.exercise.translations', 'workoutExercises.exercise.muscleGroup.translations', 'workoutExercises.sets']));
    }

    public function destroy(Request $request, Workout $workout): JsonResponse
    {
        $this->authorize('delete', $workout);

        $workout->delete();

        return response()->noContent();
    }

    public function start(Request $request, Workout $workout): WorkoutResource
    {
        $this->authorize('update', $workout);

        $workout->update([
            'status' => WorkoutStatus::Active,
            'started_at' => $workout->started_at ?? now(),
        ]);

        return new WorkoutResource($workout->refresh()->loadMissing(['workoutExercises.exercise.translations', 'workoutExercises.exercise.muscleGroup.translations', 'workoutExercises.sets']));
    }

    public function complete(Request $request, Workout $workout): WorkoutResource
    {
        $this->authorize('update', $workout);

        $this->workouts->completeWorkout($workout);

        return new WorkoutResource($workout->refresh()->loadMissing(['workoutExercises.exercise.translations', 'workoutExercises.exercise.muscleGroup.translations', 'workoutExercises.sets']));
    }

    public function addExercise(WorkoutExerciseStoreRequest $request, Workout $workout): WorkoutResource
    {
        $this->authorize('update', $workout);

        $exercise = Exercise::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $request->user()->id))
            ->findOrFail($request->integer('exercise_id'));

        DB::transaction(function () use ($workout, $exercise, $request): void {
            WorkoutExercise::query()->firstOrCreate(
                [
                    'workout_id' => $workout->id,
                    'exercise_id' => $exercise->id,
                ],
                [
                    'position' => (int) (WorkoutExercise::query()
                        ->where('workout_id', $workout->id)
                        ->max('position') ?? 0) + 1,
                    'notes' => $request->input('notes'),
                ]
            );
        });

        return new WorkoutResource($workout->refresh()->loadMissing(['workoutExercises.exercise.translations', 'workoutExercises.exercise.muscleGroup.translations', 'workoutExercises.sets']));
    }
}
