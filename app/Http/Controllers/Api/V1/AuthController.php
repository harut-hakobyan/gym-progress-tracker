<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->string('email')->toString())
            ->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), (string) $user->password)) {
            return response()->json([
                'message' => __('auth.failed'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tokenName = $request->string('device_name')->toString() ?: 'api';
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): Response
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
