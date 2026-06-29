<?php

namespace App\Services\Ranking;

use App\Models\WorldCupMatch;

// Scoring rules for tercer puesto (third-place playoff) — TBD
class ThirdPlaceRankingService
{
    public function recalculate(WorldCupMatch $match): void
    {
        (new GroupRankingService())->recalculate($match);
    }
}
