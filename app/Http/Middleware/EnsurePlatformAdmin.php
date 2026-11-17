<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isPlatformAdmin()) {
            return redirect()->route('tables.index')
                ->with('error', 'Accès réservé au Super Admin.');
        }

        return $next($request);
    }
}