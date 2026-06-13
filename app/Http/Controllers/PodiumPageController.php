<?php

namespace App\Http\Controllers;

use App\Models\LeaderboardEntry;
use App\Models\Prediction;
use Inertia\Inertia;
use Inertia\Response;

class PodiumPageController extends Controller
{
    public function __invoke(): Response
    {
        $top10 = LeaderboardEntry::with([
            'participant.predictions.match',
            'participant.matchRankings',
        ])
        ->orderBy('rank')
        ->limit(10)
        ->get();

        $entries = $top10->map(function (LeaderboardEntry $entry) {
            $participant = $entry->participant;
            $rankingsMap = $participant->matchRankings->keyBy('match_id');

            $predictions = $participant->predictions
                ->sortBy(fn (Prediction $p) => $p->match->correlativo)
                ->map(fn (Prediction $p) => [
                    'correlativo'  => $p->match->correlativo,
                    'home_team'    => $p->match->home_team,
                    'away_team'    => $p->match->away_team,
                    'group_name'   => $p->match->group_name,
                    'match_date'   => $p->match->match_date,
                    'status'       => $p->match->status,
                    'pred_home'    => $p->home_score,
                    'pred_away'    => $p->away_score,
                    'actual_home'  => $p->match->home_score,
                    'actual_away'  => $p->match->away_score,
                    'is_exact'     => $p->match->status === 'finished'
                        ? ($p->home_score === $p->match->home_score && $p->away_score === $p->match->away_score)
                        : null,
                    'points'       => $rankingsMap->get($p->match_id)?->points ?? 0,
                ])->values()->all();

            return [
                'id'                => $participant->id,
                'name'              => $participant->name,
                'slug'              => $participant->slug,
                'rank'              => $entry->rank,
                'total_points'      => $entry->total_points,
                'exact_score_count' => $entry->exact_score_count,
                'predictions'       => $predictions,
            ];
        })->values()->all();

        return Inertia::render('Podium', ['entries' => $entries]);
    }
}
