<?php

use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ExerciseController;
use App\Http\Controllers\Api\V1\GoalController;
use App\Http\Controllers\Api\V1\PersonalRecordController;
use App\Http\Controllers\Api\V1\StatisticsController;
use App\Http\Controllers\Api\V1\WorkoutController;
use App\Http\Controllers\Api\V1\WorkoutExerciseSetController;
use App\Http\Controllers\Api\V1\WorkoutTemplateExerciseController;
use App\Http\Controllers\Api\V1\WorkoutSetController;
use App\Http\Controllers\Api\V1\WorkoutTemplateController;
use Illuminate\Support\Facades\Route;

Route::post('telegram/webhook/{secret}', TelegramWebhookController::class)
    ->middleware('throttle:telegram-webhook');

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn () => response()->json(['status' => 'ok']));

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::apiResource('exercises', ExerciseController::class);
        Route::apiResource('workout-templates', WorkoutTemplateController::class);
        Route::apiResource('workouts', WorkoutController::class);
        Route::post('/workout-templates/{template}/copy', [WorkoutTemplateController::class, 'copy']);
        Route::post('/workout-templates/{workoutTemplate}/exercises', [WorkoutTemplateExerciseController::class, 'store']);
        Route::patch('/workout-templates/{workoutTemplate}/reorder', [WorkoutTemplateExerciseController::class, 'reorder']);
        Route::patch('/workout-template-exercises/{workoutTemplateExercise}', [WorkoutTemplateExerciseController::class, 'update']);
        Route::delete('/workout-template-exercises/{workoutTemplateExercise}', [WorkoutTemplateExerciseController::class, 'destroy']);

        Route::post('/workouts/{workout}/start', [WorkoutController::class, 'start']);
        Route::post('/workouts/{workout}/complete', [WorkoutController::class, 'complete']);
        Route::post('/workouts/{workout}/exercises', [WorkoutController::class, 'addExercise']);

        Route::post('/workout-exercises/{workoutExercise}/sets', [WorkoutExerciseSetController::class, 'store']);
        Route::patch('/workout-sets/{workoutSet}', [WorkoutSetController::class, 'update']);
        Route::delete('/workout-sets/{workoutSet}', [WorkoutSetController::class, 'destroy']);

        Route::get('/statistics/summary', [StatisticsController::class, 'summary']);
        Route::get('/statistics/exercises/{exercise}', [StatisticsController::class, 'exercise']);
        Route::get('/statistics/exercises/{exercise}/forecast', [StatisticsController::class, 'forecast']);

        Route::get('/personal-records', [PersonalRecordController::class, 'index']);
        Route::apiResource('goals', GoalController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});
