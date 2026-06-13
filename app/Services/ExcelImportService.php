<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Prediction;
use App\Models\WorldCupMatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImportService
{
    private array $matchCache = [];

    private array $participantCache = [];

    public function import(string $filePath): array
    {
        Log::info("Starting Excel import from {$filePath}");

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, false);

        array_shift($rows);

        $grouped = [];
        foreach ($rows as $row) {
            if (empty($row[0])) {
                continue;
            }
            $participantName = trim((string) $row[0]);
            $grouped[$participantName][] = $row;
        }

        $stats = ['participants' => 0, 'predictions' => 0, 'skipped' => 0];

        DB::transaction(function () use ($grouped, &$stats) {
            foreach ($grouped as $participantName => $predictions) {
                $participant = $this->getOrCreateParticipant($participantName);
                $stats['participants']++;

                foreach ($predictions as $row) {
                    try {
                        $this->importPrediction($participant, $row);
                        $stats['predictions']++;
                    } catch (\Throwable $e) {
                        $stats['skipped']++;
                        Log::warning("Skipped prediction row", ['participant' => $participantName, 'error' => $e->getMessage()]);
                    }
                }
            }
        });

        Log::info("Excel import completed", $stats);

        return $stats;
    }

    private function getOrCreateParticipant(string $name): Participant
    {
        if (isset($this->participantCache[$name])) {
            return $this->participantCache[$name];
        }

        $slug = Str::slug($name);
        $baseSlug = $slug;
        $i = 1;

        while (Participant::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$i}";
            $i++;
        }

        $participant = Participant::firstOrCreate(
            ['name' => $name],
            ['slug' => $slug]
        );

        $this->participantCache[$name] = $participant;

        return $participant;
    }

    private function importPrediction(Participant $participant, array $row): void
    {
        // Columns: [0]name, [1]correlativo, [2]group, [3]date, [4]home_team, [5]home_goals, [6]away_goals, [7]away_team, [8]venue
        $correlativo = (int) $row[1];
        $homeTeam = trim((string) $row[4]);
        $homeScore = (int) $row[5];
        $awayScore = (int) $row[6];
        $awayTeam = trim((string) $row[7]);

        $match = $this->getMatch($correlativo, $homeTeam, $awayTeam);

        if (! $match) {
            throw new \RuntimeException("Match not found: correlativo={$correlativo}, {$homeTeam} vs {$awayTeam}");
        }

        Prediction::updateOrCreate(
            [
                'participant_id' => $participant->id,
                'match_id' => $match->id,
            ],
            [
                'home_score' => $homeScore,
                'away_score' => $awayScore,
            ]
        );
    }

    private function getMatch(int $correlativo, string $homeTeam, string $awayTeam): ?WorldCupMatch
    {
        $key = $correlativo;

        if (isset($this->matchCache[$key])) {
            return $this->matchCache[$key];
        }

        $match = WorldCupMatch::where('correlativo', $correlativo)->first();

        if (! $match) {
            $match = WorldCupMatch::where('home_team', 'like', "%{$homeTeam}%")
                ->where('away_team', 'like', "%{$awayTeam}%")
                ->first();
        }

        if ($match) {
            $this->matchCache[$key] = $match;
        }

        return $match;
    }
}
