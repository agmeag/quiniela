<?php

namespace App\Console\Commands;

use App\Models\MatchRanking;
use App\Models\WorldCupMatch;
use App\Services\MatchSyncService;
use App\Services\RankingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('quiniela:sync {--recalculate : Also recalculate rankings after sync}')]
#[Description('Fetch live match data from the WorldCup API and update the database')]
class SyncMatches extends Command
{
    public function handle(MatchSyncService $syncService, RankingService $rankingService): int
    {
        $this->info('Syncing matches from API...');
        $log = $syncService->sync();

        if ($log->status === 'success') {
            $this->info("Fetched: {$log->matches_fetched} | Updated: {$log->matches_updated} | Duration: {$log->duration_seconds}s");

            if ($log->matches_updated > 0 || $this->option('recalculate')) {
                // Remove any stale rankings for matches that are no longer finished
                $stale = MatchRanking::whereHas('match', fn ($q) => $q->where('status', '!=', 'finished'))->delete();
                if ($stale > 0) {
                    $this->line("Pruned {$stale} stale ranking rows.");
                }

                $this->info('Recalculating rankings...');
                $matches = WorldCupMatch::where('status', 'finished')->get();
                $bar = $this->output->createProgressBar($matches->count());

                foreach ($matches as $match) {
                    $rankingService->recalculateMatchRankings($match);
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();

                $rankingService->recalculateLeaderboard();
                Cache::tags(['leaderboard', 'matches'])->flush();
                $this->info('Rankings updated. Cache cleared.');
            }

            return self::SUCCESS;
        }

        $this->error("Sync failed: {$log->message}");
        return self::FAILURE;
    }
}
