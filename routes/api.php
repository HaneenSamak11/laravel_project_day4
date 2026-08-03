<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.json')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);

        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);

        Route::middleware('admin')->group(function (): void {
            Route::post('/products', [ProductController::class, 'store']);
            Route::match(['put', 'patch'], '/products/{product}', [ProductController::class, 'update']);
            Route::delete('/products/{product}', [ProductController::class, 'destroy']);

            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{user}', [UserController::class, 'show']);
            Route::patch('/users/{user}/role', [UserController::class, 'updateRole']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
        });

        Route::get('/chatbot/history', [ChatbotController::class, 'history']);
        Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])
            ->middleware('throttle:20,1');
        Route::delete('/chatbot/history', [ChatbotController::class, 'clear']);
    });
});
