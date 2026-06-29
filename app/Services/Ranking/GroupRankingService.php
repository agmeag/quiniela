<?php

namespace App\Services\Ranking;

use App\Models\MatchRanking;
use App\Models\Prediction;
use App\Models\WorldCupMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GroupRankingService
{
    public function recalculate(WorldCupMatch $match): void
    {
        $refHome   = $match->home_score;
        $refAway   = $match->away_score;
        $refWinner = $refHome > $refAway ? 'home' : ($refAway > $refHome ? 'away' : 'draw');

        $predictions = Prediction::where('match_id', $match->id)->get();

        $scored = $predictions->map(function (Prediction $prediction) use ($match, $refHome, $refAway, $refWinner) {
            $scoreDiff = abs($prediction->home_score - $refHome)
                + abs($prediction->away_score - $refAway);

            $isExact = $prediction->home_score === $refHome
                && $prediction->away_score === $refAway;

            $correctWinner = $prediction->getWinner() === $refWinner;

            $points = $this->calculatePoints($isExact, $correctWinner, $match->stage);

            return [
                'prediction'     => $prediction,
                'score_diff'     => $scoreDiff,
                'is_exact'       => $isExact,
                'correct_winner' => $correctWinner,
                'points'         => $points,
            ];
        })->sortBy([
            ['score_diff', 'asc'],
            ['is_exact', 'desc'],
        ])->values();

        $now  = Carbon::now();
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
                        'match_id'       => $match->id,
                        'participant_id' => $item['prediction']->participant_id,
                    ],
                    [
                        'prediction_id' => $item['prediction']->id,
                        'rank'          => $rank,
                        'score_diff'    => $item['score_diff'],
                        'is_exact'      => $item['is_exact'],
                        'correct_winner'=> $item['correct_winner'],
                        'points'        => $item['points'],
                        'calculated_at' => $now,
                    ]
                );
            }
        });
    }

    private function calculatePoints(bool $isExact, bool $correctWinner, string $stage): int
    {
        $tiers = config('scoring.tiers', []);
        $tier  = $tiers[$stage] ?? config('scoring.default');

        if ($isExact) {
            return $tier['exact_score'];
        }

        if ($correctWinner) {
            return $tier['correct_winner'];
        }

        return 0;
    }
}
