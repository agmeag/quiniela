<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorldCupMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'correlativo',
        'external_id',
        'home_team',
        'away_team',
        'home_team_flag',
        'away_team_flag',
        'home_score',
        'away_score',
        'home_score_ht',
        'away_score_ht',
        'match_date',
        'stage',
        'group_name',
        'venue',
        'status',
        'minute',
        'last_synced_at',
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'last_synced_at' => 'datetime',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'home_score_ht' => 'integer',
        'away_score_ht' => 'integer',
        'minute' => 'integer',
    ];

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class, 'match_id');
    }

    public function matchRankings(): HasMany
    {
        return $this->hasMany(MatchRanking::class, 'match_id');
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function hasScore(): bool
    {
        return $this->home_score !== null && $this->away_score !== null;
    }

    public function isPredictionOpen(): bool
    {
        return now()->lt($this->match_date->copy()->subHour());
    }

    public function getScoreString(): string
    {
        if (! $this->hasScore()) {
            return '-';
        }

        return "{$this->home_score} - {$this->away_score}";
    }

    public function getWinner(): ?string
    {
        if (! $this->hasScore()) {
            return null;
        }

        if ($this->home_score > $this->away_score) {
            return 'home';
        }

        if ($this->away_score > $this->home_score) {
            return 'away';
        }

        return 'draw';
    }
}
