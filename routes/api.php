<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\SponsorController as AdminSponsorController;
use App\Http\Controllers\Public\CategoryController as PublicCategoryController;
use App\Http\Controllers\Public\CommentController as PublicCommentController;
use App\Http\Controllers\Public\PostController as PublicPostController;
use App\Http\Controllers\Public\SponsorController as PublicSponsorController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/auth')->middleware('web')->group(function (): void {
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
    Route::get('/posts/featured/list', [AdminPostController::class, 'featured']);
    Route::post('/posts/featured/reorder', [AdminPostController::class, 'reorderFeatured']);
    Route::post('/posts/{post}/feature', [AdminPostController::class, 'feature']);
    Route::post('/posts/{post}/unfeature', [AdminPostController::class, 'unfeature']);
    Route::post('/posts/{post}/publish', [AdminPostController::class, 'publish']);
    Route::post('/posts/{post}/schedule', [AdminPostController::class, 'schedule']);

    Route::get('/media', [AdminMediaController::class, 'index']);
    Route::post('/media/upload', [AdminMediaController::class, 'upload']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::get('/categories', [AdminCategoryController::class, 'index']);
    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::get('/categories/{category}', [AdminCategoryController::class, 'show']);
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);

    Route::get('/comments', [AdminCommentController::class, 'index']);
    Route::post('/comments/{comment}/approve', [AdminCommentController::class, 'approve']);
    Route::post('/comments/{comment}/reject', [AdminCommentController::class, 'reject']);
    Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy']);

    Route::get('/sponsors', [AdminSponsorController::class, 'index']);
    Route::post('/sponsors', [AdminSponsorController::class, 'store']);
    Route::get('/sponsors/{sponsor}', [AdminSponsorController::class, 'show']);
    Route::put('/sponsors/{sponsor}', [AdminSponsorController::class, 'update']);
    Route::delete('/sponsors/{sponsor}', [AdminSponsorController::class, 'destroy']);

    Route::get('/audit-logs', [AuditLogController::class, 'index']);
});

Route::prefix('public')->group(function (): void {
    Route::get('/posts/featured', [PublicPostController::class, 'featured']);
    Route::get('/posts', [PublicPostController::class, 'index']);
    Route::get('/posts/{slug}', [PublicPostController::class, 'show']);
    Route::get('/posts/{slug}/comments', [PublicCommentController::class, 'index']);
    Route::post('/posts/{slug}/comments', [PublicCommentController::class, 'store']);
    Route::get('/sponsors', [PublicSponsorController::class, 'index']);
    Route::get('/categories', [PublicCategoryController::class, 'index']);
    Route::get('/categories/{slug}/posts', [PublicCategoryController::class, 'posts']);
});
