<?php

namespace App\DTOs;

use Carbon\Carbon;

readonly class MatchData
{
    public function __construct(
        public string $externalId,
        public int $correlativo,
        public string $homeTeam,
        public string $awayTeam,
        public ?int $homeScore,
        public ?int $awayScore,
        public Carbon $matchDate,
        public string $groupName,
        public string $status,
        public ?int $minute,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        // Parse date: "06/11/2026 13:00"
        $matchDate = Carbon::createFromFormat('m/d/Y H:i', $data['local_date'] ?? '01/01/2026 00:00');

        // Determine status
        $finished = strtoupper($data['finished'] ?? 'FALSE') === 'TRUE';
        $timeElapsed = trim($data['time_elapsed'] ?? '');

        $status = 'scheduled';
        $minute = null;

        if ($finished || $timeElapsed === 'finished') {
            $status = 'finished';
        } elseif ($timeElapsed !== '' && $timeElapsed !== 'notstarted' && $timeElapsed !== 'not started') {
            $status = 'live';
            $minute = is_numeric($timeElapsed) ? (int) $timeElapsed : null;
        }

        // Scores — null if not started, int if has score
        $homeScore = null;
        $awayScore = null;
        if ($status !== 'scheduled') {
            $homeScore = is_numeric($data['home_score'] ?? null) ? (int) $data['home_score'] : null;
            $awayScore = is_numeric($data['away_score'] ?? null) ? (int) $data['away_score'] : null;
        }

        $groupName = 'Grupo ' . strtoupper($data['group'] ?? 'A');

        return new self(
            externalId: (string) ($data['id'] ?? '0'),
            correlativo: (int) ($data['id'] ?? 0),
            homeTeam: $data['home_team_name_en'] ?? '',
            awayTeam: $data['away_team_name_en'] ?? '',
            homeScore: $homeScore,
            awayScore: $awayScore,
            matchDate: $matchDate,
            groupName: $groupName,
            status: $status,
            minute: $minute,
        );
    }
}
