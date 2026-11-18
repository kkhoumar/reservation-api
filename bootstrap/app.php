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
        $middleware->alias([
            'platform_admin' => \App\Http\Middleware\EnsurePlatformAdmin::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'restaurant.access' => \App\Http\Middleware\EnsureRestaurantAccess::class,
        ]);

        // Déjà connecté et on ouvre une page « invités » (connexion, accueil) :
        // direction le bon écran selon le rôle (évite une boucle vers « / »)
        $middleware->redirectUsersTo('/app');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
