<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'leaderboard' => $this->whenLoaded('leaderboardEntry', fn () => [
                'rank' => $this->leaderboardEntry?->rank,
                'total_points' => $this->leaderboardEntry?->total_points,
                'exact_score_count' => $this->leaderboardEntry?->exact_score_count,
                'correct_winner_count' => $this->leaderboardEntry?->correct_winner_count,
                'near_prediction_count' => $this->leaderboardEntry?->near_prediction_count,
                'matches_predicted' => $this->leaderboardEntry?->matches_predicted,
            ]),
        ];
    }
}
