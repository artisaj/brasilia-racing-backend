<?php

use App\Http\Controllers\Admin\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('guest');

    Route::middleware(['auth:sanctum', 'role:admin,redator'])->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
