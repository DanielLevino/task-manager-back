<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamMembershipController;

Route::get('/ping', fn() => response()->json(['pong' => true]));

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::apiResource('tasks', TaskController::class);
    Route::apiResource('teams', TeamController::class);
    Route::apiResource('team-memberships', TeamMembershipController::class)->only(['index','store','update','destroy']);
    // Route::post('tasks/{task}/assign', [TaskController::class, 'assign']);

});
