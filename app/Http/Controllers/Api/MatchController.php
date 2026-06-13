<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MatchRankingResource;
use App\Http\Resources\MatchResource;
use App\Models\WorldCupMatch;
use App\Services\RankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class MatchController extends Controller
{
    public function __construct(private readonly RankingService $rankingService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $cacheKey = "matches.list.{$request->query('stage', 'all')}.{$request->query('search', '')}";

        $matches = Cache::tags(['matches'])->remember($cacheKey, 300, function () use ($request) {
            $query = WorldCupMatch::query()->orderBy('match_date');

            if ($request->has('stage') && $request->stage !== 'all') {
                $query->where('stage', $request->stage);
            }

            if ($request->has('search') && $request->search) {
                $search = "%{$request->search}%";
                $query->where(function ($q) use ($search) {
                    $q->where('home_team', 'like', $search)
                        ->orWhere('away_team', 'like', $search);
                });
            }

            return $query->get();
        });

        return MatchResource::collection($matches);
    }

    public function show(WorldCupMatch $match): JsonResponse
    {
        $cacheKey = "matches.{$match->id}.detail";

        $data = Cache::tags(['matches'])->remember($cacheKey, 60, function () use ($match) {
            $podium = $this->rankingService->getMatchPodium($match, 10);
            $exact = $this->rankingService->getExactPredictions($match);
            $near = $this->rankingService->getNearPredictions($match);
            $stats = $this->rankingService->getMatchStatistics($match);

            return [
                'match' => new MatchResource($match),
                'podium' => MatchRankingResource::collection($podium),
                'exact_predictions' => MatchRankingResource::collection($exact),
                'near_predictions' => MatchRankingResource::collection($near),
                'statistics' => $stats,
            ];
        });

        return response()->json($data);
    }
}
