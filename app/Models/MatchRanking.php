<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchRanking extends Model
{
    protected $fillable = [
        'match_id',
        'participant_id',
        'prediction_id',
        'rank',
        'score_diff',
        'is_exact',
        'correct_winner',
        'points',
        'calculated_at',
    ];

    protected $casts = [
        'is_exact' => 'boolean',
        'correct_winner' => 'boolean',
        'calculated_at' => 'datetime',
        'rank' => 'integer',
        'score_diff' => 'integer',
        'points' => 'integer',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(WorldCupMatch::class, 'match_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function prediction(): BelongsTo
    {
        return $this->belongsTo(Prediction::class);
    }
}
