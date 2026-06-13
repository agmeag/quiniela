<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardEntry extends Model
{
    protected $fillable = [
        'participant_id',
        'total_points',
        'exact_score_count',
        'correct_winner_count',
        'near_prediction_count',
        'matches_predicted',
        'rank',
        'calculated_at',
    ];

    protected $casts = [
        'total_points' => 'integer',
        'exact_score_count' => 'integer',
        'correct_winner_count' => 'integer',
        'near_prediction_count' => 'integer',
        'matches_predicted' => 'integer',
        'rank' => 'integer',
        'calculated_at' => 'datetime',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
