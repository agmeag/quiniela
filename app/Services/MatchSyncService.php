<?php

namespace App\Services;

use App\DTOs\MatchData;
use App\Models\SyncLog;
use App\Models\WorldCupMatch;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MatchSyncService
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('worldcup.api_url', 'https://worldcup26.ir'), '/');
    }

    public function sync(): SyncLog
    {
        $log = SyncLog::create(['type' => 'full_sync', 'status' => 'pending']);
        $startedAt = microtime(true);

        try {
            $apiMatches = $this->fetchFromApi();
            $updated = 0;

            foreach ($apiMatches as $apiData) {
                if ($this->updateMatch($apiData)) {
                    $updated++;
                }
            }

            $duration = microtime(true) - $startedAt;
            $log->markSuccess(count($apiMatches), $updated, $duration);

            Log::info('WorldCup sync completed', [
                'fetched' => count($apiMatches),
                'updated' => $updated,
                'duration_seconds' => round($duration, 2),
            ]);
        } catch (Throwable $e) {
            $log->markFailed($e->getMessage(), ['trace' => substr($e->getTraceAsString(), 0, 500)]);
            Log::error('WorldCup sync failed', ['error' => $e->getMessage()]);
        }

        return $log;
    }

    private function fetchFromApi(): array
    {
        $response = Http::timeout(config('worldcup.timeout', 30))
            ->retry(config('worldcup.retry_times', 3), config('worldcup.retry_sleep', 1000))
            ->get("{$this->apiUrl}/get/games");

        if (! $response->successful()) {
            throw new \RuntimeException("API returned HTTP {$response->status()}");
        }

        $body = $response->json();

        // The API returns { "games": [...] }
        if (is_array($body) && isset($body['games'])) {
            return $body['games'];
        }

        // Fallback: might be a plain array
        return is_array($body) ? $body : [];
    }

    private function updateMatch(array $apiData): bool
    {
        try {
            $matchData = MatchData::fromApiResponse($apiData);

            if ($matchData->correlativo < 1 || $matchData->correlativo > 72) {
                return false;
            }

            // Match by correlativo (API id == our correlativo)
            $match = WorldCupMatch::where('correlativo', $matchData->correlativo)->first();

            if (! $match) {
                Log::warning("No match found for correlativo {$matchData->correlativo}");
                return false;
            }

            $changed = $match->status !== $matchData->status
                || $match->home_score !== $matchData->homeScore
                || $match->away_score !== $matchData->awayScore
                || $match->minute !== $matchData->minute;

            $match->update([
                'external_id'    => $matchData->externalId,
                'home_score'     => $matchData->homeScore,
                'away_score'     => $matchData->awayScore,
                'status'         => $matchData->status,
                'minute'         => $matchData->minute,
                'last_synced_at' => now(),
            ]);

            return $changed;
        } catch (Throwable $e) {
            Log::warning('Failed to update match from API', [
                'data' => $apiData,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
