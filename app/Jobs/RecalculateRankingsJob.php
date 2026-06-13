<?php

namespace App\Jobs;

use App\Models\WorldCupMatch;
use App\Services\RankingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RecalculateRankingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(private readonly ?int $matchId = null)
    {
        $this->onQueue('default');
    }

    public function handle(RankingService $rankingService): void
    {
        if ($this->matchId) {
            $match = WorldCupMatch::find($this->matchId);
            if ($match) {
                $rankingService->recalculateMatchRankings($match);
            }
        } else {
            $matches = WorldCupMatch::whereNotNull('home_score')->get();
            foreach ($matches as $match) {
                $rankingService->recalculateMatchRankings($match);
            }
        }

        $rankingService->recalculateLeaderboard();
        Cache::tags(['leaderboard', 'matches'])->flush();
    }
}
