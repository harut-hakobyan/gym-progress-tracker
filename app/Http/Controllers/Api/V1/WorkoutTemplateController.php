<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkoutTemplateCopyRequest;
use App\Http\Requests\Api\V1\WorkoutTemplateStoreRequest;
use App\Http\Requests\Api\V1\WorkoutTemplateUpdateRequest;
use App\Http\Resources\Api\V1\WorkoutTemplateResource;
use App\Models\WorkoutTemplate;
use App\Services\Templates\WorkoutTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkoutTemplateController extends Controller
{
    public function __construct(
        private readonly WorkoutTemplateService $templates,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $templates = WorkoutTemplate::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => WorkoutTemplateResource::collection($templates),
        ]);
    }

    public function store(WorkoutTemplateStoreRequest $request): JsonResponse
    {
        $this->authorize('create', WorkoutTemplate::class);

        $template = WorkoutTemplate::query()->create([
            'user_id' => $request->user()->id,
            'name' => $request->string('name')->toString(),
            'description' => $request->input('description'),
            'is_active' => (bool) $request->boolean('is_active', true),
        ]);

        return response()->json([
            'data' => new WorkoutTemplateResource($template),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, WorkoutTemplate $template): WorkoutTemplateResource
    {
        $this->authorize('view', $template);

        return new WorkoutTemplateResource($template->loadMissing('templateExercises.exercise'));
    }

    public function update(WorkoutTemplateUpdateRequest $request, WorkoutTemplate $template): WorkoutTemplateResource
    {
        $this->authorize('update', $template);

        $template->update([
            'name' => $request->filled('name') ? $request->string('name')->toString() : $template->name,
            'description' => $request->input('description', $template->description),
            'is_active' => $request->has('is_active') ? (bool) $request->boolean('is_active') : $template->is_active,
        ]);

        return new WorkoutTemplateResource($template->refresh());
    }

    public function copy(WorkoutTemplateCopyRequest $request, WorkoutTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        $copy = $this->templates->copy(
            $request->user(),
            $template,
            $request->input('name')
        );

        return (new WorkoutTemplateResource($copy->loadMissing('templateExercises.exercise')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, WorkoutTemplate $template): JsonResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return response()->noContent();
    }
}
