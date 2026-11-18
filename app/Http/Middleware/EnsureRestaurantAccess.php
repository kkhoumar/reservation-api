<?php

namespace App\Http\Middleware;

use App\Support\ReadOnlyGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde d'accès aux écrans d'un maquis. Appliqué à chaque requête,
 * y compris aux actions Livewire (middleware persistant).
 *
 * - Compte désactivé : déconnexion immédiate.
 * - Super Admin : doit d'abord choisir un maquis ; il garde l'accès
 *   même si le maquis est verrouillé (pour le dépanner).
 * - Patron / employé : bloqué si son maquis est verrouillé
 *   (suspendu pour non-paiement, ou essai terminé).
 */
class EnsureRestaurantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Votre compte a été désactivé. Contactez votre responsable.');
        }

        // Mot de passe provisoire : rien d'autre tant qu'il n'est pas changé
        if ($user->mustChangePassword()) {
            return redirect()->route('password.change');
        }

        // Le patron accepte les conditions d'utilisation (et chaque nouvelle version)
        if ($user->mustAcceptTerms()) {
            return redirect()->route('terms.accept');
        }

        if ($user->isPlatformAdmin()) {
            if ($user->currentRestaurant() === null) {
                return redirect()->route('admin.restaurants')
                    ->with('error', 'Choisissez d\'abord le maquis dans lequel entrer.');
            }

            return $next($request);
        }

        $restaurant = $user->restaurant;

        if (! $restaurant) {
            abort(403, 'Aucun établissement associé à ce compte.');
        }

        if ($restaurant->isLocked()) {
            // Patron et gérante : lecture seule (ils consultent leurs chiffres).
            // Les autres n'ont rien à consulter : page « Accès suspendu ».
            if (! in_array($user->role, ['owner', 'manager'], true)) {
                return redirect()->route('account.locked');
            }

            // Les actions Livewire passent : toute écriture est de toute façon
            // refusée par ReadOnlyGuard. On empêche seulement d'ouvrir les
            // écrans qui servent à vendre.
            $isPageLoad = ! $request->hasHeader('X-Livewire');

            if ($isPageLoad && ! in_array($request->route()?->getName(), self::READ_ONLY_ROUTES, true)) {
                return redirect()->route('dashboard.index')
                    ->with('error', ReadOnlyGuard::MESSAGE);
            }
        }

        return $next($request);
    }

    /** Écrans consultables quand le maquis est en lecture seule. */
    public const READ_ONLY_ROUTES = [
        'dashboard.index',
        'sales.today',
        'cash-register.history',
        'cash-register.closed',
        'credits',
        'expenses.index',
        'stock.index',
        'exports.index',
        'exports.download',
        'users.manage',
        'settings',
    ];
}
