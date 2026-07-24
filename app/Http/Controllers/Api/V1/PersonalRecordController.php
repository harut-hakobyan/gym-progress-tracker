<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PersonalRecordResource;
use App\Services\Records\PersonalRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonalRecordController extends Controller
{
    public function __construct(
        private readonly PersonalRecordService $records,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => PersonalRecordResource::collection($this->records->latestForUser($request->user())),
        ]);
    }
}
