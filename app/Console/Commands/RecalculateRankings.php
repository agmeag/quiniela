<?php

namespace App\Console\Commands;

use App\Models\MatchRanking;
use App\Models\WorldCupMatch;
use App\Services\RankingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('quiniela:recalculate
    {--match=      : Specific match ID to recalculate}
    {--stage=      : Only recalculate matches of this stage (e.g. round_of_32)}
    {--all         : Wipe and recalculate all finished matches (use with --override)}
    {--override    : Delete existing rankings for the target scope before recalculating}
')]
#[Description('Recalculate match rankings and overall leaderboard')]
class RecalculateRankings extends Command
{
    public function handle(RankingService $rankingService): int
    {
        $matchId  = $this->option('match');
        $stage    = $this->option('stage');
        $all      = $this->option('all');
        $override = $this->option('override');

        if ($matchId) {
            $match = WorldCupMatch::find($matchId);
            if (! $match) {
                $this->error("Match {$matchId} not found.");
                return self::FAILURE;
            }

            if ($override) {
                MatchRanking::where('match_id', $match->id)->delete();
                $this->line("  Cleared existing rankings for match #{$match->id}.");
            }

            $this->info("Recalculating {$match->home_team} vs {$match->away_team}...");
            $rankingService->recalculateMatchRankings($match);

        } else {
            $query = WorldCupMatch::where('status', 'finished');

            if ($stage) {
                $query->where('stage', $stage);
                $this->info("Scope: stage = {$stage}");
            } elseif ($all) {
                $this->info('Scope: ALL finished matches');
            }

            if ($override) {
                if ($all && ! $stage) {
                    $deleted = MatchRanking::query()->delete();
                } else {
                    $ids = $query->pluck('id');
                    $deleted = MatchRanking::whereIn('match_id', $ids)->delete();
                }
                $this->line("  Cleared {$deleted} existing ranking row(s).");
            } else {
                // Default: prune rankings that belong to non-finished matches
                MatchRanking::whereHas('match', fn ($q) => $q->where('status', '!=', 'finished'))->delete();
            }

            $matches = $query->get();
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
