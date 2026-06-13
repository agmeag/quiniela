<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Prediction;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ParticipantPageController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $participant = Participant::with([
            'predictions.match',
            'matchRankings',
            'leaderboardEntry',
        ])->where('slug', $slug)->first();

        if (! $participant) {
            throw new NotFoundHttpException();
        }

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

        return Inertia::render('ParticipantDetail', [
            'participant' => [
                'id'                => $participant->id,
                'name'              => $participant->name,
                'slug'              => $participant->slug,
                'rank'              => $participant->leaderboardEntry?->rank ?? 0,
                'total_points'      => $participant->leaderboardEntry?->total_points ?? 0,
                'exact_score_count' => $participant->leaderboardEntry?->exact_score_count ?? 0,
            ],
            'predictions' => $predictions,
        ]);
    }
}
