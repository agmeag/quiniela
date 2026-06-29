<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\User;
use App\Services\AuditService;
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
                'id'                   => $u->id,
                'name'                 => $u->name,
                'email'                => $u->email,
                'role'                 => $u->role,
                'participant_id'       => $u->participant_id,
                'participant_name'     => $u->participant?->name,
                'participant_slug'     => $u->participant?->slug,
                'must_change_password' => (bool) $u->must_change_password,
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
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'email', 'unique:users,email'],
            'password'             => ['required', 'string', 'min:8'],
            'role'                 => ['required', Rule::in(['super_admin', 'admin', 'participant'])],
            'participant_id'       => ['nullable', 'exists:participants,id'],
            'must_change_password' => ['boolean'],
        ]);

        $user = User::create($data);

        AuditService::log('user.created', "Usuario creado: {$user->name} ({$user->email}) · rol: {$user->role}", 'user', $user->id);

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password'             => ['nullable', 'string', 'min:8'],
            'role'                 => ['required', Rule::in(['super_admin', 'admin', 'participant'])],
            'participant_id'       => ['nullable', 'exists:participants,id'],
            'must_change_password' => ['boolean'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        AuditService::log('user.updated', "Usuario actualizado: {$user->name} ({$user->email})", 'user', $user->id);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->password = $data['password'];
        $user->must_change_password = true;
        $user->save();

        AuditService::log('user.password_reset', "Contraseña restablecida para: {$user->name} ({$user->email})", 'user', $user->id);

        return back()->with('success', "Contraseña de {$user->name} restablecida. Deberá cambiarla al iniciar sesión.");
    }

    public function destroy(User $user): RedirectResponse
    {
        AuditService::log('user.deleted', "Usuario eliminado: {$user->name} ({$user->email})", 'user', $user->id);

        $user->delete();

        return back()->with('success', 'Usuario eliminado.');
    }

    public function bulkCreate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'base_password' => ['required', 'string', 'min:8'],
        ]);

        $participants = Participant::whereNotNull('email')
            ->whereDoesntHave('user')
            ->get();

        if ($participants->isEmpty()) {
            return back()->with('success', 'No hay participantes pendientes de cuenta.');
        }

        foreach ($participants as $participant) {
            User::create([
                'name'                 => $participant->name,
                'email'                => $participant->email,
                'password'             => $data['base_password'],
                'role'                 => 'participant',
                'participant_id'       => $participant->id,
                'must_change_password' => true,
            ]);
        }

        AuditService::log('user.bulk_created', "Creación masiva: {$participants->count()} cuentas de usuario generadas");

        return back()->with('success', "Se crearon {$participants->count()} usuarios.");
    }
}
