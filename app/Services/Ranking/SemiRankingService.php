<?php

namespace App\Services\Ranking;

use App\Models\WorldCupMatch;

// Scoring rules for semifinales — TBD
class SemiRankingService
{
    public function recalculate(WorldCupMatch $match): void
    {
        (new GroupRankingService())->recalculate($match);
    }
}
