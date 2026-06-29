<?php

namespace App\Services\Ranking;

use App\Models\WorldCupMatch;

// Scoring rules for cuartos de final (quarter-finals) — TBD
class QuarterRankingService
{
    public function recalculate(WorldCupMatch $match): void
    {
        (new GroupRankingService())->recalculate($match);
    }
}
