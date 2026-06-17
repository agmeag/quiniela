<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorldCupMatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class MatchesController extends Controller
{
    public function index(): Response
    {
        $matches = WorldCupMatch::orderBy('match_date')
            ->get()
            ->map(fn ($m) => [
                'id'             => $m->id,
                'correlativo'    => $m->correlativo,
                'home_team'      => $m->home_team,
                'home_team_flag' => $m->home_team_flag,
                'away_team'      => $m->away_team,
                'away_team_flag' => $m->away_team_flag,
                'match_date'     => $m->match_date?->format('Y-m-d H:i:s'),
                'venue'          => $m->venue,
                'group_name'     => $m->group_name,
                'stage'          => $m->stage,
                'status'         => $m->status,
            ])->values()->all();

        return Inertia::render('Admin/Matches', ['matches' => $matches]);
    }

    public function update(Request $request, WorldCupMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'home_team'      => ['required', 'string', 'max:100'],
            'home_team_flag' => ['nullable', 'string', 'max:20'],
            'away_team'      => ['required', 'string', 'max:100'],
            'away_team_flag' => ['nullable', 'string', 'max:20'],
            'match_date'     => ['required', 'date_format:Y-m-d H:i:s'],
            'venue'          => ['nullable', 'string', 'max:200'],
        ]);

        $match->update($data);
        Cache::tags(['matches'])->flush();

        return back()->with('success', "Partido #{$match->correlativo} actualizado.");
    }
}
