<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Learner files must run exactly as signed.
        $middleware->remove([TrimStrings::class, ConvertEmptyStringsToNull::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Only the main API calls this app, so every error is JSON.
        $exceptions->shouldRenderJsonWhen(fn () => true);
    })->create();
