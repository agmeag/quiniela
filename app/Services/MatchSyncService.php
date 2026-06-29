<?php

namespace App\Services;

use App\DTOs\MatchData;
use App\Models\MatchGoal;
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

            // 1. Fast path: already identified in a previous sync
            $match = WorldCupMatch::where('external_id', $matchData->externalId)->first();

            // 2. Identify by translated team names (API sends English, DB stores Spanish)
            if (! $match) {
                $homeEs = $this->translateTeam($matchData->homeTeam);
                $awayEs = $this->translateTeam($matchData->awayTeam);

                if ($homeEs && $awayEs) {
                    $match = WorldCupMatch::where('home_team', $homeEs)
                        ->where('away_team', $awayEs)
                        ->first();
                }
            }

            if (! $match) {
                Log::warning('No match found for API entry', [
                    'api_id'    => $matchData->externalId,
                    'home_team' => $matchData->homeTeam,
                    'away_team' => $matchData->awayTeam,
                ]);
                return false;
            }

            // Group stage is closed — skip already-finished group matches
            if ($match->stage === 'group' && $match->status === 'finished') {
                return false;
            }

            $changed = $match->status !== $matchData->status
                || $match->home_score !== $matchData->homeScore
                || $match->away_score !== $matchData->awayScore
                || $match->minute !== $matchData->minute;

            // Sync goals to match_goals table for non-group, live/finished matches
            $score90Update = [];
            if ($match->stage !== 'group' && in_array($matchData->status, ['live', 'finished']) && ! empty($matchData->goals)) {
                $match->goals()->delete();
                MatchGoal::insert(
                    collect($matchData->goals)->map(fn ($g) => array_merge(
                        ['match_id' => $match->id, 'created_at' => now(), 'updated_at' => now()],
                        $g
                    ))->all()
                );

                // Auto-compute 90-min score when AET detected
                if ($matchData->status === 'finished') {
                    if ($matchData->hasExtraTimeGoals()) {
                        $score90Update = [
                            'home_score_90' => $matchData->goalsAt90('home'),
                            'away_score_90' => $matchData->goalsAt90('away'),
                        ];
                    } else {
                        $score90Update = ['home_score_90' => null, 'away_score_90' => null];
                    }
                }
            }

            $match->update(array_merge([
                'external_id'    => $matchData->externalId,
                'home_score'     => $matchData->homeScore,
                'away_score'     => $matchData->awayScore,
                'status'         => $matchData->status,
                'minute'         => $matchData->minute,
                'last_synced_at' => now(),
            ], $score90Update));

            return $changed;
        } catch (Throwable $e) {
            Log::warning('Failed to update match from API', [
                'data' => $apiData,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function translateTeam(string $name): ?string
    {
        static $map = [
            'Mexico'                       => 'México',
            'South Africa'                 => 'Sudáfrica',
            'South Korea'                  => 'Corea Del Sur',
            'Czech Republic'               => 'Rep. Checa',
            'Czechia'                      => 'Rep. Checa',
            'Canada'                       => 'Canadá',
            'Bosnia and Herzegovina'       => 'Bosnia Y Herzeg.',
            'Bosnia & Herzegovina'         => 'Bosnia Y Herzeg.',
            'United States'                => 'Estados Unidos',
            'USA'                          => 'Estados Unidos',
            'Qatar'                        => 'Catar',
            'Switzerland'                  => 'Suiza',
            'Brazil'                       => 'Brasil',
            'Morocco'                      => 'Marruecos',
            'Haiti'                        => 'Haití',
            'Scotland'                     => 'Escocia',
            'Australia'                    => 'Australia',
            'Turkey'                       => 'Turquía',
            'Türkiye'                      => 'Turquía',
            'Germany'                      => 'Alemania',
            'Curaçao'                      => 'Curazao',
            'Curacao'                      => 'Curazao',
            'Netherlands'                  => 'Países Bajos',
            'Japan'                        => 'Japón',
            'Ivory Coast'                  => 'Costa De Marfil',
            "Côte d'Ivoire"                => 'Costa De Marfil',
            "Cote d'Ivoire"                => 'Costa De Marfil',
            'Ecuador'                      => 'Ecuador',
            'Sweden'                       => 'Suecia',
            'Tunisia'                      => 'Túnez',
            'Spain'                        => 'España',
            'Cape Verde'                   => 'Cabo Verde',
            'Belgium'                      => 'Bélgica',
            'Egypt'                        => 'Egipto',
            'Saudi Arabia'                 => 'Arabia Saudita',
            'Uruguay'                      => 'Uruguay',
            'Iran'                         => 'Irán',
            'New Zealand'                  => 'Nueva Zelanda',
            'France'                       => 'Francia',
            'Senegal'                      => 'Senegal',
            'Iraq'                         => 'Irak',
            'Norway'                       => 'Noruega',
            'Argentina'                    => 'Argentina',
            'Algeria'                      => 'Argelia',
            'Austria'                      => 'Austria',
            'Jordan'                       => 'Jordania',
            'Portugal'                     => 'Portugal',
            'DR Congo'                     => 'Rep. Del Congo',
            'Congo DR'                     => 'Rep. Del Congo',
            'Democratic Republic of Congo' => 'Rep. Del Congo',
            'England'                      => 'Inglaterra',
            'Croatia'                      => 'Croacia',
            'Ghana'                        => 'Ghana',
            'Panama'                       => 'Panamá',
            'Uzbekistan'                   => 'Uzbekistán',
            'Colombia'                     => 'Colombia',
        ];

        return $map[$name] ?? null;
    }
}
