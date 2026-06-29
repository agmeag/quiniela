<?php

namespace App\Services\Ranking;

use App\Models\WorldCupMatch;

class Round16RankingService
{
    public function recalculate(WorldCupMatch $match): void
    {
        (new Round32RankingService())->recalculate($match);
    }
}
