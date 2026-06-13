<?php

namespace App\Console\Commands;

use App\Models\MatchRanking;
use App\Models\WorldCupMatch;
use App\Services\RankingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('quiniela:recalculate {--match= : Specific match ID to recalculate}')]
#[Description('Recalculate match rankings and overall leaderboard')]
class RecalculateRankings extends Command
{
    public function handle(RankingService $rankingService): int
    {
        $matchId = $this->option('match');

        if ($matchId) {
            $match = WorldCupMatch::find($matchId);
            if (! $match) {
                $this->error("Match {$matchId} not found.");

                return self::FAILURE;
            }
            $this->info("Recalculating match {$match->home_team} vs {$match->away_team}...");
            $rankingService->recalculateMatchRankings($match);
        } else {
            MatchRanking::whereHas('match', fn ($q) => $q->where('status', '!=', 'finished'))->delete();
            $matches = WorldCupMatch::where('status', 'finished')->get();
            $bar = $this->output->createProgressBar($matches->count());

            foreach ($matches as $match) {
                $rankingService->recalculateMatchRankings($match);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->info('Recalculating leaderboard...');
        $rankingService->recalculateLeaderboard();

        Cache::tags(['leaderboard', 'matches'])->flush();
        $this->info('Done. Cache cleared.');

        return self::SUCCESS;
    }
}
