<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'pipeline.secret' => \App\Http\Middleware\VerifyPipelineSecret::class,
        ]);

        // No storefront customer accounts exist — the only guarded area is
        // /admin, so an unauthenticated hit there should land on the admin
        // login screen rather than Laravel's default (nonexistent) `login`
        // route.
        $middleware->redirectGuestsTo('/admin/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
