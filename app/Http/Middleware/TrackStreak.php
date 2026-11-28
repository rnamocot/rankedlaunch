<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Counts a signed-in user's visit towards their daily streak (one write per day at most). */
class TrackStreak
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->user()?->recordActivity();

        return $next($request);
    }
}
