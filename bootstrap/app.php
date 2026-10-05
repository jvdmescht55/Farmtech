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
            'module' => \App\Http\Middleware\EnsureModuleAccess::class,
        ]);

        // Staff land on the admin login; device customers on the public one.
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin', 'admin/*') ? '/admin/login' : '/login');
        $middleware->redirectUsersTo(fn ($request) => $request->user()?->canAccessAdminPanel() ? '/admin' : '/app');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
