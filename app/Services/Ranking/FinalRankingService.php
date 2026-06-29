<?php

namespace App\Services\Ranking;

use App\Models\WorldCupMatch;

// Scoring rules for la final — TBD
class FinalRankingService
{
    public function recalculate(WorldCupMatch $match): void
    {
        (new GroupRankingService())->recalculate($match);
    }
}
