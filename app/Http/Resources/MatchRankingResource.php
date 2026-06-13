<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchRankingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->rank,
            'participant' => [
                'id' => $this->participant->id,
                'name' => $this->participant->name,
                'slug' => $this->participant->slug,
            ],
            'prediction' => [
                'home_score' => $this->prediction->home_score,
                'away_score' => $this->prediction->away_score,
                'score_string' => $this->prediction->getScoreString(),
                'winner' => $this->prediction->getWinner(),
            ],
            'score_diff' => $this->score_diff,
            'is_exact' => $this->is_exact,
            'correct_winner' => $this->correct_winner,
            'points' => $this->points,
        ];
    }
}
