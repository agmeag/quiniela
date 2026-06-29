<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchGoal extends Model
{
    protected $table = 'match_goals';

    protected $fillable = [
        'match_id',
        'team',
        'scorer',
        'minute',
        'minute_value',
        'is_penalty',
        'is_own_goal',
        'is_extra_time',
        'sort_order',
    ];

    protected $casts = [
        'minute_value'  => 'integer',
        'is_penalty'    => 'boolean',
        'is_own_goal'   => 'boolean',
        'is_extra_time' => 'boolean',
        'sort_order'    => 'integer',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(WorldCupMatch::class, 'match_id');
    }
}
