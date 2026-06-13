<?php

use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:120,1')->group(function () {
    Route::get('matches', [MatchController::class, 'index']);
    Route::get('matches/{match}', [MatchController::class, 'show']);

    Route::get('leaderboard', [LeaderboardController::class, 'index']);
    Route::get('podium', [LeaderboardController::class, 'podium']);

    Route::get('statistics', [StatisticsController::class, 'index']);
});
