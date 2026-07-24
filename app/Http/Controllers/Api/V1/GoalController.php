<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserGoalStatus;
use App\Enums\UserGoalType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GoalStoreRequest;
use App\Http\Requests\Api\V1\GoalUpdateRequest;
use App\Http\Resources\Api\V1\UserGoalResource;
use App\Models\Exercise;
use App\Models\UserGoal;
use App\Services\Goals\GoalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GoalController extends Controller
{
    public function __construct(
        private readonly GoalService $goals,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $goals = UserGoal::query()
            ->with('exercise')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (UserGoal $goal): array {
                $goalArray = (new UserGoalResource($goal))->resolve();

                return array_merge($goalArray, [
                    'progress' => $this->goals->progress($goal),
                ]);
            });

        return response()->json([
            'data' => $goals,
        ]);
    }

    public function store(GoalStoreRequest $request): JsonResponse
    {
        $this->authorize('create', UserGoal::class);

        $exercise = null;

        if ($request->filled('exercise_id')) {
            $exercise = Exercise::query()
                ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $request->user()->id))
                ->findOrFail($request->integer('exercise_id'));
        }

        $goal = $this->goals->create(
            $request->user(),
            UserGoalType::from($request->string('type')->toString()),
            (float) $request->input('target_value'),
            $request->filled('target_date') ? Carbon::createFromFormat('Y-m-d', $request->string('target_date')->toString()) : null,
            $exercise
        );

        $goal->load('exercise');

        return response()->json([
            'data' => new UserGoalResource($goal),
        ], Response::HTTP_CREATED);
    }

    public function update(GoalUpdateRequest $request, UserGoal $goal): UserGoalResource
    {
        $this->authorize('update', $goal);

        $goal->update([
            'type' => $request->filled('type') ? $request->string('type')->toString() : $goal->type->value,
            'target_value' => $request->filled('target_value') ? (float) $request->input('target_value') : $goal->target_value,
            'target_date' => $request->filled('target_date') ? $request->date('target_date') : $goal->target_date,
            'status' => $request->filled('status') ? $request->string('status')->toString() : $goal->status->value,
            'exercise_id' => $request->filled('exercise_id') ? $request->integer('exercise_id') : $goal->exercise_id,
        ]);

        return new UserGoalResource($goal->refresh()->load('exercise'));
    }

    public function destroy(Request $request, UserGoal $goal): JsonResponse
    {
        $this->authorize('delete', $goal);

        $goal->delete();

        return response()->noContent();
    }
}
