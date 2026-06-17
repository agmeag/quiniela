<?php

namespace App\Services;

use App\Models\LeaderboardEntry;
use App\Models\MatchRanking;
use App\Models\Participant;
use App\Models\Prediction;
use App\Models\WorldCupMatch;
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

        $predictions = Prediction::where('match_id', $match->id)->get();

        $scored = $predictions->map(function (Prediction $prediction) use ($match) {
            $scoreDiff = abs($prediction->home_score - $match->home_score)
                + abs($prediction->away_score - $match->away_score);

            $isExact = $prediction->home_score === $match->home_score
                && $prediction->away_score === $match->away_score;

            $correctWinner = $prediction->getWinner() === $match->getWinner();

            $points = $this->calculatePoints($isExact, $correctWinner, $match->stage);

            return [
                'prediction' => $prediction,
                'score_diff' => $scoreDiff,
                'is_exact' => $isExact,
                'correct_winner' => $correctWinner,
                'points' => $points,
            ];
        })->sortBy([
            ['score_diff', 'asc'],
            ['is_exact', 'desc'],
        ])->values();

        $now = Carbon::now();
        $rank = 1;

        DB::transaction(function () use ($scored, $match, $now, &$rank) {
            foreach ($scored as $index => $item) {
                if ($index > 0) {
                    $prev = $scored[$index - 1];
                    if ($item['score_diff'] !== $prev['score_diff']) {
                        $rank = $index + 1;
                    }
                }

                MatchRanking::updateOrCreate(
                    [
                        'match_id' => $match->id,
                        'participant_id' => $item['prediction']->participant_id,
                    ],
                    [
                        'prediction_id' => $item['prediction']->id,
                        'rank' => $rank,
                        'score_diff' => $item['score_diff'],
                        'is_exact' => $item['is_exact'],
                        'correct_winner' => $item['correct_winner'],
                        'points' => $item['points'],
                        'calculated_at' => $now,
                    ]
                );
            }
        });
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
                $correctWinnerCount = $rankings->where('correct_winner', true)->count();
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
