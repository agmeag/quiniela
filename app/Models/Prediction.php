<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    protected $fillable = [
        'participant_id',
        'match_id',
        'home_score',
        'away_score',
    ];

    protected $casts = [
        'home_score' => 'integer',
        'away_score' => 'integer',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(WorldCupMatch::class, 'match_id');
    }

    public function getWinner(): string
    {
        if ($this->home_score > $this->away_score) {
            return 'home';
        }

        if ($this->away_score > $this->home_score) {
            return 'away';
        }

        return 'draw';
    }

    public function getScoreString(): string
    {
        return "{$this->home_score} - {$this->away_score}";
    }
}
