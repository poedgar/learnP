<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Learner PHP must run exactly as written; whitespace is significant.
        $middleware->trimStrings(except: ['code']);

        // The web app signs in with a session cookie; the mobile app uses API tokens.
        $middleware->statefulApi();

        // Laravel Cloud's edge proxy sits in front of the app; trust it so requests carry
        // the public host and scheme (Sanctum compares the host with the page's origin).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
