<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Public\PostController as PublicPostController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('guest');

    Route::middleware(['auth:sanctum', 'role:admin,redator'])->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin,redator'])->group(function (): void {
    Route::get('/posts', [AdminPostController::class, 'index']);
    Route::post('/posts', [AdminPostController::class, 'store']);
    Route::get('/posts/{post}', [AdminPostController::class, 'show']);
    Route::put('/posts/{post}', [AdminPostController::class, 'update']);
    Route::delete('/posts/{post}', [AdminPostController::class, 'destroy']);
    Route::post('/posts/{post}/publish', [AdminPostController::class, 'publish']);
    Route::post('/posts/{post}/schedule', [AdminPostController::class, 'schedule']);
});

Route::prefix('public')->group(function (): void {
    Route::get('/posts', [PublicPostController::class, 'index']);
    Route::get('/posts/{slug}', [PublicPostController::class, 'show']);
});
