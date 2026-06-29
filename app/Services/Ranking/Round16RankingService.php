<?php

namespace App\Services\Ranking;

use App\Models\MatchRanking;
use App\Models\Prediction;
use App\Models\WorldCupMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Round16RankingService
{
    public function recalculate(WorldCupMatch $match): void
    {
        $hasAET = $match->home_score_90 !== null;

        // 7-pt reference: score at 90 min (falls back to full-time when no AET)
        $refHome = $hasAET ? $match->home_score_90 : $match->home_score;
        $refAway = $hasAET ? $match->away_score_90 : $match->away_score;

        // 5-pt reference: correct 90-min outcome (winner or draw)
        $refWinner = $refHome > $refAway ? 'home' : ($refAway > $refHome ? 'away' : 'draw');

        $predictions = Prediction::where('match_id', $match->id)->get();

        $scored = $predictions->map(function (Prediction $prediction) use ($refHome, $refAway, $refWinner) {
            $scoreDiff = abs($prediction->home_score - $refHome)
                + abs($prediction->away_score - $refAway);

            $isExact = $prediction->home_score === $refHome
                && $prediction->away_score === $refAway;

            // 5-pt condition: correct 90-min outcome only
            $correctWinner = $prediction->getWinner() === $refWinner;
            $earnsFive     = $correctWinner;

            $points = $this->calculatePoints($isExact, $earnsFive, 'round_of_16');

            return [
                'prediction'     => $prediction,
                'score_diff'     => $scoreDiff,
                'is_exact'       => $isExact,
                'correct_winner' => $earnsFive,
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

    private function calculatePoints(bool $isExact, bool $earnsFive, string $stage): int
    {
        $tiers = config('scoring.tiers', []);
        $tier  = $tiers[$stage] ?? config('scoring.default');

        if ($isExact) {
            return $tier['exact_score'];
        }

        if ($earnsFive) {
            return $tier['correct_winner'];
        }

        return 0;
    }
}
