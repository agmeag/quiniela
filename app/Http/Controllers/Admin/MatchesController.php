<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorldCupMatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
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
                'external_id'    => $m->external_id,
                'closes_at'      => $m->match_date?->copy()->subHour()->toIso8601String(),
            ])->values()->all();

        return Inertia::render('Admin/Matches', ['matches' => $matches]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'home_team'      => ['required', 'string', 'max:100'],
            'home_team_flag' => ['nullable', 'string', 'max:20'],
            'away_team'      => ['required', 'string', 'max:100'],
            'away_team_flag' => ['nullable', 'string', 'max:20'],
            'match_date'     => ['required', 'date_format:Y-m-d H:i:s'],
            'venue'          => ['nullable', 'string', 'max:200'],
            'stage'          => ['required', Rule::in(['group', 'round_of_32', 'round_of_16', 'quarter', 'semi', 'third_place', 'final'])],
            'group_name'     => ['nullable', 'required_if:stage,group', 'string', 'max:100'],
            'external_id'    => ['nullable', 'string', 'max:50', Rule::unique('matches', 'external_id')],
        ]);

        $data['correlativo'] = (WorldCupMatch::max('correlativo') ?? 0) + 1;
        $data['status']      = 'notstarted';

        WorldCupMatch::create($data);
        Cache::tags(['matches'])->flush();

        return back()->with('success', 'Partido creado correctamente.');
    }

    public function update(Request $request, WorldCupMatch $match): RedirectResponse
    {
        // Only super_admin can edit once the prediction deadline has passed (1 hour before match)
        $deadline = $match->match_date->copy()->subHour();
        if (now()->gte($deadline) && ! $request->user()->isSuperAdmin()) {
            abort(403, 'Solo el super admin puede editar este partido una vez cerrado el período de predicción.');
        }

        $data = $request->validate([
            'home_team'      => ['required', 'string', 'max:100'],
            'home_team_flag' => ['nullable', 'string', 'max:20'],
            'away_team'      => ['required', 'string', 'max:100'],
            'away_team_flag' => ['nullable', 'string', 'max:20'],
            'match_date'     => ['required', 'date_format:Y-m-d H:i:s'],
            'venue'          => ['nullable', 'string', 'max:200'],
            'stage'          => ['required', Rule::in(['group', 'round_of_32', 'round_of_16', 'quarter', 'semi', 'third_place', 'final'])],
            'group_name'     => ['nullable', 'required_if:stage,group', 'string', 'max:100'],
            'external_id'    => ['nullable', 'string', 'max:50', Rule::unique('matches', 'external_id')->ignore($match->id)],
        ]);

        $match->update($data);
        Cache::tags(['matches'])->flush();

        return back()->with('success', "Partido #{$match->correlativo} actualizado.");
    }
}
