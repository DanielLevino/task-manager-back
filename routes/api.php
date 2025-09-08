<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;

Route::get('/ping', fn() => response()->json(['pong' => true]));

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::apiResource('tasks', TaskController::class);
    Route::post('tasks/{task}/assign', [TaskController::class, 'assign']);

});
