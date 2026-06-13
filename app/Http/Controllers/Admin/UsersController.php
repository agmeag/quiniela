<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function index(): Response
    {
        $users = User::with('participant')
            ->orderBy('role')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id'               => $u->id,
                'name'             => $u->name,
                'email'            => $u->email,
                'role'             => $u->role,
                'participant_id'   => $u->participant_id,
                'participant_name' => $u->participant?->name,
                'participant_slug' => $u->participant?->slug,
            ])->values()->all();

        $participants = Participant::orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'slug' => $p->slug])
            ->values()->all();

        return Inertia::render('Admin/Users', [
            'users'        => $users,
            'participants' => $participants,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'unique:users,email'],
            'password'       => ['required', 'string', 'min:8'],
            'role'           => ['required', Rule::in(['super_admin', 'participant'])],
            'participant_id' => ['nullable', 'exists:participants,id'],
        ]);

        User::create($data);

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password'       => ['nullable', 'string', 'min:8'],
            'role'           => ['required', Rule::in(['super_admin', 'participant'])],
            'participant_id' => ['nullable', 'exists:participants,id'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return back()->with('success', 'Usuario eliminado.');
    }
}
