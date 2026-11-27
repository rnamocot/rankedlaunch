<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Signs out banned users the moment they make a request. */
class EnsureNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->banned_at) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, 'Account suspended.');
            }

            return redirect()->route('login')->withErrors(['email' => 'This account has been suspended. Contact us if you think this is a mistake.']);
        }

        return $next($request);
    }
}
