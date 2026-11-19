<?php

namespace App\Providers;

use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureRestaurantAccess;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dates relatives en français (« il y a 2 heures » et non « 2 hours ago »)
        \Carbon\Carbon::setLocale('fr');

        if ($proxies = config('app.trusted_proxies')) {
            \Illuminate\Http\Middleware\TrustProxies::at(
                $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies))
            );
        }

        // Les actions Livewire (clics, formulaires) passent par /livewire/update.
        // Ces middlewares y sont réappliqués avec les paramètres de la route
        // d'origine : un maquis verrouillé ou un rôle insuffisant reste bloqué
        // même après le chargement de la page.
        Livewire::addPersistentMiddleware([
            EnsureRestaurantAccess::class,
            EnsureRole::class,
            EnsurePlatformAdmin::class,
        ]);
    }
}
