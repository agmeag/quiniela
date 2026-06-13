<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = $request->query('search', '');
        $sort = $request->query('sort', 'rank');
        $direction = $request->query('direction', 'asc');
        $perPage = 100;

        $cacheKey = "leaderboard.page.{$page}.{$search}.{$sort}.{$direction}";

        $data = Cache::tags(['leaderboard'])->remember($cacheKey, 120, function () use ($page, $perPage, $search, $sort, $direction) {
            $allowedSorts = ['rank', 'total_points', 'exact_score_count', 'correct_winner_count', 'matches_predicted'];
            $sort = in_array($sort, $allowedSorts) ? $sort : 'rank';
            $direction = $direction === 'desc' ? 'desc' : 'asc';

            $query = Participant::with('leaderboardEntry')
                ->join('leaderboard_entries', 'participants.id', '=', 'leaderboard_entries.participant_id')
                ->select('participants.*');

            if ($search) {
                $query->where('participants.name', 'ilike', "%{$search}%");
            }

            $sortColumn = $sort === 'rank' ? 'leaderboard_entries.rank' : "leaderboard_entries.{$sort}";
            $query->orderBy($sortColumn, $direction);

            $paginated = $query->paginate($perPage, ['participants.*'], 'page', $page);

            $entries = collect($paginated->items())->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'rank' => $p->leaderboardEntry?->rank ?? 0,
                'total_points' => $p->leaderboardEntry?->total_points ?? 0,
                'exact_score_count' => $p->leaderboardEntry?->exact_score_count ?? 0,
                'correct_winner_count' => $p->leaderboardEntry?->correct_winner_count ?? 0,
                'near_prediction_count' => $p->leaderboardEntry?->near_prediction_count ?? 0,
                'matches_predicted' => $p->leaderboardEntry?->matches_predicted ?? 0,
            ])->values()->all();

            return [
                'entries' => $entries,
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ],
            ];
        });

        return Inertia::render('Leaderboard', array_merge($data, [
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
        ]));
    }
}
