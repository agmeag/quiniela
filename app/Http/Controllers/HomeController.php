<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\WorldCupMatch;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $data = Cache::tags(['leaderboard', 'matches'])->remember('home.data', 120, function () {
            $topParticipants = Participant::with('leaderboardEntry')
                ->join('leaderboard_entries', 'participants.id', '=', 'leaderboard_entries.participant_id')
                ->select('participants.*')
                ->orderBy('leaderboard_entries.rank')
                ->limit(10)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'rank' => $p->leaderboardEntry?->rank ?? 0,
                    'total_points' => $p->leaderboardEntry?->total_points ?? 0,
                    'exact_score_count' => $p->leaderboardEntry?->exact_score_count ?? 0,
                    'correct_winner_count' => $p->leaderboardEntry?->correct_winner_count ?? 0,
                ])
                ->values()
                ->all();

            $liveMatches = WorldCupMatch::where('status', 'live')->orderBy('match_date')->limit(4)->get();
            $upcomingMatches = WorldCupMatch::where('status', 'scheduled')->orderBy('match_date')->limit(4)->get();
            $recentMatches = WorldCupMatch::where('status', 'finished')->orderBy('match_date', 'desc')->limit(4)->get();

            $featuredSource = $liveMatches->isNotEmpty() ? $liveMatches
                : ($upcomingMatches->isNotEmpty() ? $upcomingMatches : $recentMatches);

            $serializeMatch = fn ($m) => [
                'id' => $m->id,
                'correlativo' => $m->correlativo,
                'home_team' => $m->home_team,
                'away_team' => $m->away_team,
                'home_team_flag' => $m->home_team_flag,
                'away_team_flag' => $m->away_team_flag,
                'home_score' => $m->home_score,
                'away_score' => $m->away_score,
                'match_date' => $m->match_date?->toIso8601String(),
                'stage' => $m->stage,
                'group_name' => $m->group_name,
                'status' => $m->status,
                'venue' => $m->venue,
                'minute' => $m->minute,
            ];

            return [
                'top_participants' => $topParticipants,
                'featured_matches' => $featuredSource->map($serializeMatch)->values()->all(),
                'has_live' => $liveMatches->isNotEmpty(),
            ];
        });

        return Inertia::render('Home', $data);
    }
}
