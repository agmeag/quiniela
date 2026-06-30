<?php

namespace App\Services;

use App\Models\LeaderboardEntry;
use App\Models\MatchRanking;
use App\Models\Participant;
use App\Models\Prediction;
use App\Models\WorldCupMatch;
use App\Services\Ranking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RankingService
{
    public function recalculateMatchRankings(WorldCupMatch $match): void
    {
        if ($match->status !== 'finished' || ! $match->hasScore()) {
            return;
        }

        if (in_array($match->stage, ['round_of_32', 'round_of_16'])) {
            $this->ensureScore90Correct($match);
        }

        $service = match ($match->stage) {
            'group'       => new Ranking\GroupRankingService(),
            'round_of_32' => new Ranking\Round32RankingService(),
            'round_of_16' => new Ranking\Round16RankingService(),
            'quarter'     => new Ranking\QuarterRankingService(),
            'semi'        => new Ranking\SemiRankingService(),
            'third_place' => new Ranking\ThirdPlaceRankingService(),
            'final'       => new Ranking\FinalRankingService(),
            default       => new Ranking\GroupRankingService(),
        };

        $service->recalculate($match);
    }

    /**
     * Re-evaluate is_extra_time on stored match_goals and correct home_score_90
     * / away_score_90 before scoring.  Goals written as "90+5'" are stoppage
     * time in regulation (base=90); only goals where the base minute itself
     * exceeds 90 with no suffix (e.g. "98'", "105'") are real extra time.
     */
    private function ensureScore90Correct(WorldCupMatch $match): void
    {
        $goals = $match->goals()->get();

        if ($goals->isEmpty()) {
            return;
        }

        $hasRealAET = false;

        foreach ($goals as $goal) {
            $correct = $this->isRealExtraTime($goal->minute);

            if ($goal->is_extra_time !== $correct) {
                $goal->update(['is_extra_time' => $correct]);
            }

            if ($correct) {
                $hasRealAET = true;
            }
        }

        if (! $hasRealAET && $match->home_score_90 !== null) {
            $match->update(['home_score_90' => null, 'away_score_90' => null]);
        } elseif ($hasRealAET) {
            $goals = $match->goals()->get();
            $match->update([
                'home_score_90' => $goals->where('is_extra_time', false)->where('team', 'home')->count(),
                'away_score_90' => $goals->where('is_extra_time', false)->where('team', 'away')->count(),
            ]);
        }
    }

    private function isRealExtraTime(string $minuteStr): bool
    {
        if (! preg_match('/(\d+)(?:[\'"]?\+(\d+))?\s*\'/', $minuteStr, $m)) {
            return false;
        }

        $base  = (int) $m[1];
        $added = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;

        return $base > 90 && $added === 0;
    }

    public function recalculateLeaderboard(): void
    {
        $participants = Participant::all();
        $now = Carbon::now();

        DB::transaction(function () use ($participants, $now) {
            $entries = $participants->map(function (Participant $participant) use ($now) {
                $rankings = MatchRanking::where('participant_id', $participant->id)->get();

                $totalPoints = $rankings->sum('points');
                $exactCount = $rankings->where('is_exact', true)->count();
                $correctWinnerCount = $rankings->where('correct_winner', true)->where('is_exact', false)->count();
                $nearCount = $rankings->where('is_exact', false)
                    ->where('score_diff', '<=', 2)
                    ->count();
                $matchesPredicted = Prediction::where('participant_id', $participant->id)->count();

                return [
                    'participant_id' => $participant->id,
                    'total_points' => $totalPoints,
                    'exact_score_count' => $exactCount,
                    'correct_winner_count' => $correctWinnerCount,
                    'near_prediction_count' => $nearCount,
                    'matches_predicted' => $matchesPredicted,
                    'calculated_at' => $now,
                ];
            })->sortByDesc('total_points')->values();

            $rank = 1;
            foreach ($entries as $index => $entry) {
                if ($index > 0 && $entry['total_points'] !== $entries[$index - 1]['total_points']) {
                    $rank = $index + 1;
                }

                LeaderboardEntry::updateOrCreate(
                    ['participant_id' => $entry['participant_id']],
                    array_merge($entry, ['rank' => $rank])
                );
            }
        });
    }

    private function calculatePoints(bool $isExact, bool $correctWinner, string $stage): int
    {
        $tiers   = config('scoring.tiers', []);
        $tier    = $tiers[$stage] ?? config('scoring.default');

        if ($isExact) {
            return $tier['exact_score'];
        }

        if ($correctWinner) {
            return $tier['correct_winner'];
        }

        return 0;
    }

    public function getMatchPodium(WorldCupMatch $match, int $limit = 10): Collection
    {
        return MatchRanking::with('participant', 'prediction')
            ->where('match_id', $match->id)
            ->orderBy('rank')
            ->orderBy('score_diff')
            ->limit($limit)
            ->get();
    }

    public function getExactPredictions(WorldCupMatch $match): Collection
    {
        return MatchRanking::with('participant', 'prediction')
            ->where('match_id', $match->id)
            ->where('is_exact', true)
            ->orderBy('rank')
            ->get();
    }

    public function getNearPredictions(WorldCupMatch $match): Collection
    {
        return MatchRanking::with('participant', 'prediction')
            ->where('match_id', $match->id)
            ->where('is_exact', false)
            ->orderBy('score_diff')
            ->orderBy('rank')
            ->get();
    }

    public function getMatchStatistics(WorldCupMatch $match): array
    {
        $predictions = Prediction::where('match_id', $match->id)->get();
        $total = $predictions->count();

        if ($total === 0) {
            return [
                'total_predictions' => 0,
                'exact_count' => 0,
                'most_common' => null,
                'home_win_pct' => 0,
                'draw_pct' => 0,
                'away_win_pct' => 0,
            ];
        }

        $homeWins = $predictions->filter(fn ($p) => $p->home_score > $p->away_score)->count();
        $draws = $predictions->filter(fn ($p) => $p->home_score === $p->away_score)->count();
        $awayWins = $predictions->filter(fn ($p) => $p->away_score > $p->home_score)->count();

        $mostCommon = $predictions
            ->groupBy(fn ($p) => "{$p->home_score}-{$p->away_score}")
            ->sortByDesc(fn ($group) => $group->count())
            ->keys()
            ->first();

        $exactCount = $match->hasScore()
            ? $predictions->filter(fn ($p) => $p->home_score === $match->home_score && $p->away_score === $match->away_score)->count()
            : 0;

        return [
            'total_predictions' => $total,
            'exact_count' => $exactCount,
            'most_common' => $mostCommon,
            'home_win_pct' => round(($homeWins / $total) * 100, 1),
            'draw_pct' => round(($draws / $total) * 100, 1),
            'away_win_pct' => round(($awayWins / $total) * 100, 1),
        ];
    }
}
