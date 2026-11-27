<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Hostinger serves the site through its own front proxy; trust it so HTTPS is detected.
        $middleware->trustProxies(at: '*');
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->web(append: [\App\Http\Middleware\EnsureNotBanned::class]);
        $middleware->alias(['admin' => \App\Http\Middleware\EnsureAdmin::class]);
        // Beacons can't send a CSRF token; the click endpoint only bumps a counter.
        $middleware->preventRequestForgery(except: ['webhooks/*', 'apps/*/click']);
        // New makers who click "Submit" should land on sign-up, not "Welcome back".
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('submit*') ? route('register') : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
