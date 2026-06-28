<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Participant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'email',
    ];

    protected static function booted(): void
    {
        static::creating(function (Participant $participant) {
            if (empty($participant->slug)) {
                $participant->slug = Str::slug($participant->name);
            }
        });
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    public function matchRankings(): HasMany
    {
        return $this->hasMany(MatchRanking::class);
    }

    public function leaderboardEntry(): HasOne
    {
        return $this->hasOne(LeaderboardEntry::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
