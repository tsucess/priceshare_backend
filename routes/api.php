<?php

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ─── Public Auth Routes ─────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// ─── Public Posts (read-only) ─────────────────────────────────────────────────
Route::get('posts',       [PostController::class, 'index']);
Route::get('posts/{post}', [PostController::class, 'show']);
Route::get('posts/{post}/comments', [CommentController::class, 'index']);

// ─── Public Alerts & Dashboard ─────────────────────────────────────────────
Route::get('alerts',    [AlertController::class, 'index']);
Route::get('dashboard', [AlertController::class, 'dashboard']);

// ─── Protected Routes ─────────────────────────────────────────────────────────
// NOTE: /users/me MUST be registered before /users/{user} to avoid the wildcard
// consuming the literal string "me" as a model ID.
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('auth/logout',          [AuthController::class, 'logout']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);

    // Current User — registered BEFORE the public /users/{user} wildcard
    Route::get('users/me',           [UserController::class, 'me']);
    Route::put('users/me',           [UserController::class, 'update']);
    Route::delete('users/me',        [UserController::class, 'destroy']);
    Route::put('users/me/settings',  [UserController::class, 'updateSettings']);

    // Posts (write)
    Route::post('posts',            [PostController::class, 'store']);
    Route::put('posts/{post}',      [PostController::class, 'update']);
    Route::delete('posts/{post}',   [PostController::class, 'destroy']);
    Route::post('posts/{post}/like', [PostController::class, 'like']);
    Route::post('posts/{post}/vote', [PostController::class, 'vote']);

    // Comments (write)
    Route::post('posts/{post}/comments',             [CommentController::class, 'store']);
    Route::delete('posts/{post}/comments/{comment}', [CommentController::class, 'destroy']);

    // Image Upload
    Route::post('upload/image', [UploadController::class, 'image']);
});

// ─── Public User Profile (wildcard — after /users/me above) ──────────────────
Route::get('users/{user}',       [UserController::class, 'show']);
Route::get('users/{user}/posts', [UserController::class, 'posts']);
