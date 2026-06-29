<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\MatchesController as AdminMatchesController;
use App\Http\Controllers\Admin\SyncController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardPageController;
use App\Http\Controllers\MatchPageController;
use App\Http\Controllers\ParticipantPageController;
use App\Http\Controllers\PodiumPageController;
use App\Http\Controllers\PredictionController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [LoginController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated content
Route::middleware('auth')->group(function () {
    // Password change — excluded from EnsurePasswordChanged loop via route name
    Route::get('/account/change-password', [PasswordController::class, 'show'])
        ->name('account.password.change');
    Route::post('/account/change-password', [PasswordController::class, 'update'])
        ->name('account.password.update');

    Route::get('/', HomeController::class)->name('home');
    Route::get('/matches', [MatchPageController::class, 'index'])->name('matches.index');
    Route::get('/matches/{match}', [MatchPageController::class, 'show'])->name('matches.show');
    Route::get('/leaderboard', LeaderboardPageController::class)->name('leaderboard');
    Route::get('/podium', PodiumPageController::class)->name('podium');
    Route::get('/participants/{slug}', ParticipantPageController::class)->name('participants.show');

    // Mis predicciones (participant-facing; participant_id guard in controller)
    Route::get('/mis-predicciones', [PredictionController::class, 'index'])->name('predictions.index');
    Route::post('/mis-predicciones', [PredictionController::class, 'upsert'])->name('predictions.upsert');
});

// Admin — accessible by both admin and super_admin
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/matches', [AdminMatchesController::class, 'index'])->name('matches.index');
    Route::post('/matches', [AdminMatchesController::class, 'store'])->name('matches.store');
    Route::put('/matches/{match}', [AdminMatchesController::class, 'update'])->name('matches.update');
    Route::post('/sync', [SyncController::class, 'trigger'])->name('sync.trigger');
    Route::get('/sync/status', [SyncController::class, 'status'])->name('sync.status');
    Route::get('/export/predictions', [ExportController::class, 'predictions'])->name('export.predictions');
});

// Admin — super_admin only
Route::prefix('admin')->name('admin.')->middleware(['auth', 'superadmin'])->group(function () {
    Route::post('/import', [DashboardController::class, 'import'])->name('import');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    // bulk-create BEFORE {user} to avoid route collision
    Route::post('/users/bulk-create', [UsersController::class, 'bulkCreate'])->name('users.bulk-create');
    Route::get('/users', [UsersController::class, 'index'])->name('users.index');
    Route::post('/users', [UsersController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UsersController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/reset-password', [UsersController::class, 'resetPassword'])->name('users.reset-password');
});
