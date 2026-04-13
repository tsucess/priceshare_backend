<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\CategoryTagController;
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

// ─── Public Taxonomy ─────────────────────────────────────────────────────────
Route::get('categories', [CategoryTagController::class, 'categories']);
Route::get('tags',       [CategoryTagController::class, 'tags']);

// ─── Admin Routes (auth + admin role required) ────────────────────────────────
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Overview
    Route::get('stats', [AdminController::class, 'stats']);

    // User management
    Route::get('users',                        [AdminController::class, 'listUsers']);
    Route::get('users/{user}',                 [AdminController::class, 'showUser']);
    Route::put('users/{user}',                 [AdminController::class, 'editUser']);
    Route::post('users/{user}/ban',            [AdminController::class, 'banUser']);
    Route::post('users/{user}/unban',          [AdminController::class, 'unbanUser']);
    Route::post('users/{user}/promote',        [AdminController::class, 'promoteUser']);
    Route::post('users/{user}/demote',         [AdminController::class, 'demoteUser']);
    Route::post('users/{user}/shadow-ban',     [AdminController::class, 'shadowBanUser']);
    Route::post('users/{user}/unshadow-ban',   [AdminController::class, 'unshadowBanUser']);
    Route::delete('users/{user}',              [AdminController::class, 'deleteUser']);

    // Post management
    Route::get('posts',                  [AdminController::class, 'listPosts']);
    Route::put('posts/{post}',           [AdminController::class, 'editPost']);
    Route::post('posts/{post}/flag',     [AdminController::class, 'flagPost']);
    Route::post('posts/{post}/unflag',   [AdminController::class, 'unflagPost']);
    Route::post('posts/{post}/hide',     [AdminController::class, 'hidePost']);
    Route::post('posts/{post}/unhide',   [AdminController::class, 'unhidePost']);
    Route::delete('posts/{post}',        [AdminController::class, 'deletePost']);

    // Comment management
    Route::get('comments',               [AdminController::class, 'listComments']);
    Route::delete('comments/{comment}',  [AdminController::class, 'deleteComment']);

    // Price alert management
    Route::get('alerts',                 [AdminController::class, 'listAlerts']);
    Route::post('alerts',                [AdminController::class, 'storeAlert']);
    Route::put('alerts/{alert}',         [AdminController::class, 'updateAlert']);
    Route::delete('alerts/{alert}',      [AdminController::class, 'deleteAlert']);

    // Category management
    Route::get('categories',                       [AdminController::class, 'listCategories']);
    Route::post('categories',                      [AdminController::class, 'storeCategory']);
    Route::put('categories/{category}',            [AdminController::class, 'updateCategory']);
    Route::delete('categories/{category}',         [AdminController::class, 'deleteCategory']);

    // Tag management
    Route::get('tags',                             [AdminController::class, 'listTags']);
    Route::post('tags',                            [AdminController::class, 'storeTag']);
    Route::put('tags/{tag}',                       [AdminController::class, 'updateTag']);
    Route::delete('tags/{tag}',                    [AdminController::class, 'deleteTag']);
});
