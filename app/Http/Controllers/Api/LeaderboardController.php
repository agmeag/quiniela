<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ParticipantResource;
use App\Models\LeaderboardEntry;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LeaderboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(10, (int) $request->query('per_page', 25)));
        $search = $request->query('search', '');
        $sort = $request->query('sort', 'rank');
        $direction = $request->query('direction', 'asc');

        $cacheKey = "leaderboard.{$page}.{$perPage}.{$search}.{$sort}.{$direction}";

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

            return $query->paginate($perPage, ['participants.*'], 'page', $page);
        });

        return response()->json([
            'data' => ParticipantResource::collection($data->items()),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    public function podium(): JsonResponse
    {
        $data = Cache::tags(['leaderboard'])->remember('leaderboard.podium', 120, function () {
            return Participant::with('leaderboardEntry')
                ->join('leaderboard_entries', 'participants.id', '=', 'leaderboard_entries.participant_id')
                ->select('participants.*')
                ->orderBy('leaderboard_entries.rank')
                ->limit(10)
                ->get();
        });

        return response()->json([
            'data' => ParticipantResource::collection($data),
        ]);
    }
}
