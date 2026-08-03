<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);

    Route::get('/users/me/events', [EventController::class, 'myEvents']);

    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::patch('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
    Route::post('/events/{event}/register', [EventController::class, 'register']);
    Route::delete('/events/{event}/register', [EventController::class, 'cancelRegistration']);

    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::post('/bookmarks/{event}', [BookmarkController::class, 'store']);
    Route::delete('/bookmarks/{event}', [BookmarkController::class, 'destroy']);
});
