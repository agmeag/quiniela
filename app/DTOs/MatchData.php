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
        /** @var array<array{team:string,scorer:string,minute:string,minute_value:int,is_penalty:bool,is_own_goal:bool,is_extra_time:bool,sort_order:int}> */
        public array $goals,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        // Parse date: "06/11/2026 13:00"
        $matchDate = Carbon::createFromFormat('m/d/Y H:i', $data['local_date'] ?? '01/01/2026 00:00');

        // Determine status
        $finished    = strtoupper($data['finished'] ?? 'FALSE') === 'TRUE';
        $timeElapsed = trim($data['time_elapsed'] ?? '');

        $status = 'notstarted';
        $minute = null;

        if ($finished || strtolower($timeElapsed) === 'finished') {
            $status = 'finished';
        } elseif ($timeElapsed !== '' && $timeElapsed !== 'notstarted' && $timeElapsed !== 'not started') {
            $status = 'live';
            $minute = is_numeric($timeElapsed) ? (int) $timeElapsed : null;
        }

        // Scores — only read when the match is live or finished
        $homeScore = null;
        $awayScore = null;
        if ($status === 'live' || $status === 'finished') {
            $homeScore = is_numeric($data['home_score'] ?? null) ? (int) $data['home_score'] : null;
            $awayScore = is_numeric($data['away_score'] ?? null) ? (int) $data['away_score'] : null;
        }

        $groupName = 'Grupo ' . strtoupper($data['group'] ?? 'A');

        // Parse goals from both scorer fields into structured goal objects
        $goals     = [];
        $sortOrder = 0;

        foreach (self::parseScorersField($data['home_scorers'] ?? 'null') as $entry) {
            $goal = self::parseGoal($entry, 'home', $sortOrder++);
            if ($goal !== null) {
                $goals[] = $goal;
            }
        }

        foreach (self::parseScorersField($data['away_scorers'] ?? 'null') as $entry) {
            $goal = self::parseGoal($entry, 'away', $sortOrder++);
            if ($goal !== null) {
                $goals[] = $goal;
            }
        }

        // Sort chronologically so home/away goals are interleaved by minute
        usort($goals, fn ($a, $b) => $a['minute_value'] <=> $b['minute_value']);

        return new self(
            externalId:  (string) ($data['id'] ?? '0'),
            correlativo: (int) ($data['id'] ?? 0),
            homeTeam:    $data['home_team_name_en'] ?? '',
            awayTeam:    $data['away_team_name_en'] ?? '',
            homeScore:   $homeScore,
            awayScore:   $awayScore,
            matchDate:   $matchDate,
            groupName:   $groupName,
            status:      $status,
            minute:      $minute,
            goals:       $goals,
        );
    }

    /**
     * Parse the API's PHP-array-style scorer string into individual entries.
     *
     * API format: "{\"Messi 27'\",\"Mbappé 67'\"}"
     */
    private static function parseScorersField(string $raw): array
    {
        $raw = trim($raw);

        if ($raw === 'null' || $raw === '' || $raw === '{}') {
            return [];
        }

        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            $raw = substr($raw, 1, -1);
        }

        if (trim($raw) === '') {
            return [];
        }

        $entries = str_getcsv($raw, ',', '"');

        return array_values(array_filter(array_map('trim', $entries)));
    }

    /**
     * Parse a single scorer string into a structured goal array.
     *
     * Handles all API minute formats:
     *   "Messi 27'"          → minute_value: 27
     *   "Player 90+5'"       → minute_value: 95, is_extra_time: true
     *   "Player 90'+5'"      → minute_value: 95, is_extra_time: true
     *   "Player 45'+5'(p)"   → minute_value: 50, is_penalty: true
     *   "Player 7'(OG)"      → minute_value: 7,  is_own_goal: true
     *   "Player 95'"         → minute_value: 95, is_extra_time: true
     */
    private static function parseGoal(string $raw, string $team, int $sortOrder): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $isPenalty = (bool) preg_match('/\(p\)/i', $raw);
        $isOwnGoal = (bool) preg_match('/\(OG\)/i', $raw);

        // Match minute notation: digits, optional ('|+)digits, closing apostrophe
        // Handles: 27'  90+5'  90'+5'  45'+5'  95'
        if (! preg_match('/(\d+)(?:[\'"]?\+(\d+))?\s*\'/', $raw, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $minuteStart = (int) $m[0][1];
        $baseMinute  = (int) $m[1][0];
        $addedTime   = isset($m[2]) && $m[2][0] !== '' ? (int) $m[2][0] : 0;
        $minuteValue = $baseMinute + $addedTime;

        return [
            'team'          => $team,
            'scorer'        => trim(substr($raw, 0, $minuteStart)),
            'minute'        => trim(substr($raw, $minuteStart)),
            'minute_value'  => $minuteValue,
            'is_penalty'    => $isPenalty,
            'is_own_goal'   => $isOwnGoal,
            'is_extra_time' => $minuteValue > 90,
            'sort_order'    => $sortOrder,
        ];
    }

    /** Returns true if any goal in this match was scored in extra time (minute > 90). */
    public function hasExtraTimeGoals(): bool
    {
        foreach ($this->goals as $g) {
            if ($g['is_extra_time']) {
                return true;
            }
        }

        return false;
    }

    /** Count goals scored at or before 90 minutes for the given team. */
    public function goalsAt90(string $team): int
    {
        return count(array_filter(
            $this->goals,
            fn ($g) => $g['team'] === $team && ! $g['is_extra_time']
        ));
    }
}
