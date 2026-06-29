<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            AuditService::log('auth.login_failed', "Intento de inicio de sesión fallido para: {$credentials['email']}");

            return back()->withErrors(['email' => 'Credenciales incorrectas.']);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        AuditService::log('auth.login', "Inicio de sesión exitoso");

        if ($user->isSuperAdmin()) {
            return redirect()->intended('/admin');
        }

        if ($user->participant) {
            return redirect()->intended("/participants/{$user->participant->slug}");
        }

        return redirect()->intended('/');
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditService::log('auth.logout', "Cierre de sesión");

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
