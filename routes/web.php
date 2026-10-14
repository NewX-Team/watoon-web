<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FilmController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('films/{id}', [FilmController::class, 'show'])->name('films.show');
    Route::get('films/{film}/episodes/{episode}', [FilmController::class, 'watchEpisode'])->name('films.episodes.watch');
});

require __DIR__.'/settings.php';
