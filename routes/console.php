<?php

use App\Jobs\SyncMatchesJob;
use App\Jobs\RecalculateRankingsJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SyncMatchesJob)->everyFiveMinutes()->withoutOverlapping();
Schedule::job(new RecalculateRankingsJob)->everyThirtyMinutes()->withoutOverlapping();
