<?php

namespace App\Jobs;

use App\Models\WorldCupMatch;
use App\Services\MatchSyncService;
use App\Services\RankingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class FullSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(MatchSyncService $syncService, RankingService $rankingService): void
    {
        Cache::put('admin:sync:status', 'running', 600);

        try {
            $syncService->sync();

            $matches = WorldCupMatch::where('status', 'finished')->get();
            foreach ($matches as $match) {
                $rankingService->recalculateMatchRankings($match);
            }

            $rankingService->recalculateLeaderboard();
            Cache::tags(['leaderboard', 'matches'])->flush();
        } finally {
            Cache::forget('admin:sync:status');
        }
    }

    public function failed(\Throwable $e): void
    {
        Cache::forget('admin:sync:status');
    }
}
