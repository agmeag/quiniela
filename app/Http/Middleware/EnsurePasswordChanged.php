<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            if (! $request->routeIs('account.password.change', 'account.password.update', 'logout')) {
                return redirect()->route('account.password.change');
            }
        }

        return $next($request);
    }
}
