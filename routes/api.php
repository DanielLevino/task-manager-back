<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamMembershipController;
use App\Http\Controllers\HolidayController;

Route::get('/ping', fn() => response()->json(['pong' => true]));

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::apiResource('tasks', TaskController::class)->middleware(['store' => 'idempotency']);
    Route::apiResource('teams', TeamController::class)->middleware(['store' => 'idempotency']);
    Route::apiResource('team-memberships', TeamMembershipController::class)->only(['store', 'update']);
    Route::post('tasks/{task}/assign', [TaskController::class, 'assign']);

    Route::get('/holidays/check', [HolidayController::class, 'check']);

});
