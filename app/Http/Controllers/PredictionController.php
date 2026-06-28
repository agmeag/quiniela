<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use App\Models\WorldCupMatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PredictionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $user->participant_id) {
            return Inertia::render('Predictions', [
                'participant' => null,
                'matches'     => [],
                'stage'       => $request->query('stage', 'group'),
            ]);
        }

        $participant = $user->participant;
        $stage       = $request->query('stage', 'group');

        $matches = WorldCupMatch::where('stage', $stage)
            ->orderBy('match_date')
            ->get();

        $existingPredictions = Prediction::where('participant_id', $participant->id)
            ->whereIn('match_id', $matches->pluck('id'))
            ->get()
            ->keyBy('match_id');

        $matchData = $matches->map(function (WorldCupMatch $m) use ($existingPredictions) {
            $pred = $existingPredictions->get($m->id);

            return [
                'id'             => $m->id,
                'correlativo'    => $m->correlativo,
                'home_team'      => $m->home_team,
                'home_team_flag' => $m->home_team_flag,
                'away_team'      => $m->away_team,
                'away_team_flag' => $m->away_team_flag,
                'match_date'     => $m->match_date?->format('Y-m-d H:i:s'),
                'group_name'     => $m->group_name,
                'stage'          => $m->stage,
                'status'         => $m->status,
                'venue'          => $m->venue,
                // ISO with -06:00 offset so JS new Date() is TZ-safe
                'closes_at'      => $m->match_date?->copy()->subHour()->toIso8601String(),
                'prediction'     => $pred ? [
                    'home_score' => $pred->home_score,
                    'away_score' => $pred->away_score,
                ] : null,
            ];
        })->values()->all();

        return Inertia::render('Predictions', [
            'participant' => ['name' => $participant->name],
            'matches'     => $matchData,
            'stage'       => $stage,
        ]);
    }

    public function upsert(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->participant_id) {
            return back()->with('error', 'Tu cuenta no está vinculada a un participante.');
        }

        $request->validate([
            'predictions'               => ['required', 'array', 'min:1'],
            'predictions.*.match_id'    => ['required', 'integer', 'exists:matches,id'],
            'predictions.*.home_score'  => ['required', 'integer', 'min:0', 'max:30'],
            'predictions.*.away_score'  => ['required', 'integer', 'min:0', 'max:30'],
        ]);

        $participantId = $user->participant_id;
        $items         = $request->input('predictions');
        $matchIds      = array_column($items, 'match_id');

        $matchesById = WorldCupMatch::whereIn('id', $matchIds)->get()->keyBy('id');

        $rejected = [];
        foreach ($items as $item) {
            $match = $matchesById->get($item['match_id']);
            if (! $match || ! $match->isPredictionOpen()) {
                $rejected[] = $match?->home_team . ' vs ' . $match?->away_team;
            }
        }

        if (! empty($rejected)) {
            throw ValidationException::withMessages([
                'predictions' => 'El plazo de pronóstico ha cerrado para: ' . implode(', ', $rejected) . '.',
            ]);
        }

        $saved = 0;
        foreach ($items as $item) {
            Prediction::updateOrCreate(
                ['participant_id' => $participantId, 'match_id' => $item['match_id']],
                ['home_score' => $item['home_score'], 'away_score' => $item['away_score']]
            );
            $saved++;
        }

        return back()->with('success', "{$saved} pronóstico" . ($saved === 1 ? '' : 's') . " guardado" . ($saved === 1 ? '' : 's') . ".");
    }
}
