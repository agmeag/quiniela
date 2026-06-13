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

class SyncMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(MatchSyncService $syncService, RankingService $rankingService): void
    {
        $log = $syncService->sync();

        if ($log->status === 'success' && $log->matches_updated > 0) {
            $liveMatches = WorldCupMatch::where('status', '!=', 'scheduled')
                ->whereNotNull('home_score')
                ->get();

            foreach ($liveMatches as $match) {
                $rankingService->recalculateMatchRankings($match);
            }

            $rankingService->recalculateLeaderboard();

            Cache::tags(['leaderboard', 'matches'])->flush();
        }
    }
}
