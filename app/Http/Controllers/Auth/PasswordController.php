<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Account/ChangePassword');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->password = $request->password;
        $user->must_change_password = false;
        $user->save();

        $redirect = match (true) {
            $user->isSuperAdmin(), $user->isAdmin() => '/admin',
            $user->isParticipant()                  => '/mis-predicciones',
            default                                 => '/',
        };

        return redirect($redirect)->with('success', 'Contraseña actualizada correctamente.');
    }
}
