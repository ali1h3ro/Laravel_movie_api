<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\MovieController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\AuthController;
use GuzzleHttp\Middleware;

// Route::get('/movies', [MovieController::class, 'show']);
// Route::get('/movies/{id}', [MovieController::class, 'index']);
// Route::post('/movies/{id}/review', [ReviewController::class, 'store']);



Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('movies/search', [MovieController::class, 'search']);
        Route::post('movies/{movie}/review', [ReviewController::class, 'store']);
        Route::apiResource('movies', MovieController::class)->only(['index', 'show']);
        Route::get('movies/{movieId}/reviews', [ReviewController::class, 'index']);
        Route::get('user/reviews', [ReviewController::class, 'getUserReviews']);

    });
});
