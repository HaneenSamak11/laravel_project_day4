<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ChatbotController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login'));

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    Route::middleware('admin')->group(function (): void {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::match(['put', 'patch'], '/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');
    Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])
        ->middleware('throttle:20,1')
        ->name('chatbot.ask');
    Route::delete('/chatbot/history', [ChatbotController::class, 'clear'])->name('chatbot.clear');
});
