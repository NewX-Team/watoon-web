<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FilmController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\SubscriptionPaymentController;
use App\Http\Controllers\SubscriptionTierController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::get('pricing', [SubscriptionTierController::class, 'pricing'])->name('pricing');
Route::get('subscription/pricing', fn () => redirect()->route('pricing'));
Route::get('plans', fn () => redirect()->route('pricing'));

// Midtrans Webhook
Route::post('midtrans/webhook', [MidtransWebhookController::class, 'handle'])->name('midtrans.webhook');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('home', fn () => redirect()->route('dashboard'))->name('dashboard.home');
    Route::get('favorite', fn () => redirect()->route('dashboard', ['tab' => 'favorite']));
    Route::get('favorites', fn () => redirect()->route('dashboard', ['tab' => 'favorite']))->name('favorites');
    Route::get('subscribe', fn () => redirect()->route('dashboard', ['tab' => 'subscribe']))->name('subscribe');
    Route::get('subscribes', fn () => redirect()->route('dashboard', ['tab' => 'subscribe']));
    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('message', fn () => redirect()->route('messages.index'));
    Route::post('subscription/tier', [SubscriptionTierController::class, 'update'])->name('subscription.tier.update');
    Route::post('subscription/checkout', [SubscriptionPaymentController::class, 'checkout'])->name('subscription.checkout');
    Route::get('subscription/orders/{orderId}/status', [SubscriptionPaymentController::class, 'status'])->name('subscription.orders.status');
    Route::post('subscription/orders/{orderId}/confirm', [SubscriptionPaymentController::class, 'confirm'])->name('subscription.orders.confirm');
    Route::post('subscription/orders/{orderId}/simulate', [SubscriptionPaymentController::class, 'simulate'])->name('subscription.orders.simulate');
    Route::get('films/{id}', [FilmController::class, 'show'])->name('films.show');
    Route::get('films/{film}/episodes/{episode}', [FilmController::class, 'watchEpisode'])->name('films.episodes.watch');
});

require __DIR__.'/settings.php';
