<?php

namespace App\Http\Middleware;

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
            return redirect()->route('account.locked');
        }

        return $next($request);
    }
}
