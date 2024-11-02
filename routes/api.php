<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ReviewController;

Route::get('/movies', [MovieController::class, 'show']);
Route::get('/movies/{id}', [MovieController::class, 'index']);
Route::post('/movies/{id}/review', [ReviewController::class, 'store']);
