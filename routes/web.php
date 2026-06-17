<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MatchesController as AdminMatchesController;
use App\Http\Controllers\Admin\SyncController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardPageController;
use App\Http\Controllers\MatchPageController;
use App\Http\Controllers\ParticipantPageController;
use App\Http\Controllers\PodiumPageController;
use Illuminate\Support\Facades\Route;

// Authenticated content
Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/matches', [MatchPageController::class, 'index'])->name('matches.index');
    Route::get('/matches/{match}', [MatchPageController::class, 'show'])->name('matches.show');
    Route::get('/leaderboard', LeaderboardPageController::class)->name('leaderboard');
    Route::get('/podium', PodiumPageController::class)->name('podium');
    Route::get('/participants/{slug}', ParticipantPageController::class)->name('participants.show');
});

// Auth
Route::get('/login', [LoginController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Admin — accessible by both admin and super_admin
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/matches', [AdminMatchesController::class, 'index'])->name('matches.index');
    Route::put('/matches/{match}', [AdminMatchesController::class, 'update'])->name('matches.update');
    Route::post('/sync', [SyncController::class, 'trigger'])->name('sync.trigger');
    Route::get('/sync/status', [SyncController::class, 'status'])->name('sync.status');
});

// Admin — super_admin only
Route::prefix('admin')->name('admin.')->middleware(['auth', 'superadmin'])->group(function () {
    Route::post('/import', [DashboardController::class, 'import'])->name('import');
    Route::get('/users', [UsersController::class, 'index'])->name('users.index');
    Route::post('/users', [UsersController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UsersController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');
});
