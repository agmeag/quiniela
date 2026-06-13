<?php

namespace App\Http\Controllers;

use App\Models\LeaderboardEntry;
use App\Models\Prediction;
use App\Models\WorldCupMatch;
use App\Services\RankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class MatchPageController extends Controller
{
    public function __construct(private readonly RankingService $rankingService) {}

    public function index(Request $request): Response
    {
        $stage = $request->query('stage', 'all');
        $search = $request->query('search', '');

        $cacheKey = "matches.page.{$stage}.{$search}";
        $matches = Cache::tags(['matches'])->remember($cacheKey, 120, function () use ($stage, $search) {
            $query = WorldCupMatch::query()->orderBy('match_date');

            if ($stage && $stage !== 'all') {
                $query->where('stage', $stage);
            }

            if ($search) {
                $s = "%{$search}%";
                $query->where(fn ($q) => $q->where('home_team', 'like', $s)->orWhere('away_team', 'like', $s));
            }

            return $query->get()->map(fn ($m) => [
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
                'venue' => $m->venue,
                'status' => $m->status,
                'minute' => $m->minute,
            ])->values()->all();
        });

        $stages = Cache::remember('matches.stages', 3600, fn () =>
            WorldCupMatch::select('stage')->distinct()->pluck('stage')->sort()->values()->all()
        );

        return Inertia::render('Matches', [
            'matches' => $matches,
            'stages' => $stages,
            'filters' => ['stage' => $stage, 'search' => $search],
        ]);
    }

    public function show(WorldCupMatch $match): Response
    {
        $isLive = $match->status === 'live';

        // Live matches are never cached — score can change at any moment
        $cacheKey = "matches.{$match->id}.page";
        $ttl = $isLive ? 0 : 60;

        $build = function () use ($match, $isLive) {
            $podium = $this->rankingService->getMatchPodium($match, 10);
            $exact  = $this->rankingService->getExactPredictions($match);
            $near   = $this->rankingService->getNearPredictions($match);
            $stats  = $this->rankingService->getMatchStatistics($match);

            $serializeRanking = fn ($r) => [
                'rank'             => $r->rank,
                'participant_name' => $r->participant->name,
                'participant_slug' => $r->participant->slug,
                'home_score'       => $r->prediction->home_score,
                'away_score'       => $r->prediction->away_score,
                'score_diff'       => $r->score_diff,
                'is_exact'         => $r->is_exact,
                'correct_winner'   => $r->correct_winner,
                'points'           => $r->points,
            ];

            // All predictions for this match, sorted by leaderboard rank then name
            $ranks = LeaderboardEntry::pluck('rank', 'participant_id');

            $allPredictions = Prediction::with('participant')
                ->where('match_id', $match->id)
                ->get()
                ->sortBy(fn ($p) => [
                    $ranks->get($p->participant_id, 9999),
                    $p->participant->name,
                ])
                ->map(fn ($p) => [
                    'participant_name'  => $p->participant->name,
                    'participant_slug'  => $p->participant->slug,
                    'leaderboard_rank'  => $ranks->get($p->participant_id),
                    'pred_home'         => $p->home_score,
                    'pred_away'         => $p->away_score,
                    'is_live_matching'  => $isLive && $match->home_score !== null
                        && $p->home_score === $match->home_score
                        && $p->away_score === $match->away_score,
                ])->values()->all();

            // Participants whose prediction matches the current live score right now
            $liveMatching = $isLive
                ? collect($allPredictions)->where('is_live_matching', true)->values()->all()
                : [];

            return [
                'match' => [
                    'id'             => $match->id,
                    'correlativo'    => $match->correlativo,
                    'home_team'      => $match->home_team,
                    'away_team'      => $match->away_team,
                    'home_team_flag' => $match->home_team_flag,
                    'away_team_flag' => $match->away_team_flag,
                    'home_score'     => $match->home_score,
                    'away_score'     => $match->away_score,
                    'home_score_ht'  => $match->home_score_ht,
                    'away_score_ht'  => $match->away_score_ht,
                    'match_date'     => $match->match_date?->toIso8601String(),
                    'last_synced_at' => $match->last_synced_at?->toIso8601String(),
                    'stage'          => $match->stage,
                    'group_name'     => $match->group_name,
                    'venue'          => $match->venue,
                    'status'         => $match->status,
                    'minute'         => $match->minute,
                ],
                'live_matching'     => $liveMatching,
                'all_predictions'   => $allPredictions,
                'podium'            => $podium->map($serializeRanking)->values()->all(),
                'exact_predictions' => $exact->map($serializeRanking)->values()->all(),
                'near_predictions'  => $near->map($serializeRanking)->values()->all(),
                'statistics'        => $stats,
            ];
        };

        $data = $isLive
            ? $build()
            : Cache::tags(['matches'])->remember($cacheKey, $ttl, $build);

        return Inertia::render('MatchDetail', $data);
    }
}
