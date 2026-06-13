<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaderboardEntry;
use App\Models\Prediction;
use App\Models\SyncLog;
use App\Models\WorldCupMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class StatisticsController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Cache::tags(['leaderboard', 'matches'])->remember('statistics.global', 120, function () {
            $totalMatches = WorldCupMatch::count();
            $liveMatches = WorldCupMatch::where('status', 'live')->count();
            $finishedMatches = WorldCupMatch::where('status', 'finished')->count();
            $totalParticipants = LeaderboardEntry::count();
            $lastSync = SyncLog::where('status', 'success')->latest()->first();

            return [
                'total_matches' => $totalMatches,
                'live_matches' => $liveMatches,
                'finished_matches' => $finishedMatches,
                'scheduled_matches' => $totalMatches - $liveMatches - $finishedMatches,
                'total_participants' => $totalParticipants,
                'total_predictions' => Prediction::count(),
                'last_sync_at' => $lastSync?->updated_at?->toIso8601String(),
                'last_sync_status' => $lastSync?->status,
            ];
        });

        return response()->json(['data' => $data]);
    }
}
