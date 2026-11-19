<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();

        if (!$user || (!$user->isPlatformAdmin() && !in_array($user->role, $roles))) {
            return redirect()->route($user?->isAssociate() ? 'dashboard.index' : 'tables.index')
                ->with('error', "Cette page n'est pas accessible avec votre rôle actuel.");
        }

        return $next($request);
    }
}