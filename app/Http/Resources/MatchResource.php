<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'correlativo' => $this->correlativo,
            'home_team' => $this->home_team,
            'away_team' => $this->away_team,
            'home_team_flag' => $this->home_team_flag,
            'away_team_flag' => $this->away_team_flag,
            'home_score' => $this->home_score,
            'away_score' => $this->away_score,
            'home_score_ht' => $this->home_score_ht,
            'away_score_ht' => $this->away_score_ht,
            'match_date' => $this->match_date?->toIso8601String(),
            'stage' => $this->stage,
            'group_name' => $this->group_name,
            'venue' => $this->venue,
            'status' => $this->status,
            'minute' => $this->minute,
            'score_string' => $this->getScoreString(),
            'winner' => $this->getWinner(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
        ];
    }
}
